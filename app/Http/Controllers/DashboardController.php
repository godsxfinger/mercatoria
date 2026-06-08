<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Order;
use App\Models\Dispute;
use App\Models\Message;
use App\Models\Wishlist;
use Exception;

class DashboardController extends Controller
{
    public function index($username = null)
    {
        try {
            $loggedInUser = Auth::user();
            
            if (!$loggedInUser) {
                Log::error('Unauthenticated user tried to access dashboard');
                return redirect()->route('login')->with('error', 'Please login to access the dashboard.');
            }

            if ($username) {
                $user = User::where('username', $username)->firstOrFail();
            } else {
                $user = $loggedInUser;
            }

            $profile = $user->profile;

            if (!$profile) {
                Log::info('Creating profile for user', ['user_id' => $user->id]);
                $profile = $user->profile()->create();
            }

            $pgpKey = $user->pgpKey;

            // Determine user role
            $userRole = $this->determineUserRole($user);

            $isOwnProfile = $user->id === $loggedInUser->id;

            // Determine what information to show based on user roles and ownership
            $showFullInfo = $isOwnProfile || $loggedInUser->isAdmin();

            if (!$showFullInfo && $username) {
                Log::info('Public dashboard profile view', [
                    'viewer_id' => $loggedInUser->id,
                    'target_user_id' => $user->id,
                    'target_username' => $username,
                ]);
            }

            // Decrypt the description if it exists, otherwise set a default message
            $description = $profile->description ? Crypt::decryptString($profile->description) : "This user hasn't added a description yet.";

            $metrics = [];
            $recentEvents = collect();
            $liveActivityCount = 0;
            $wishlistCount = 0;
            $chartSeries = collect();
            $chartPoints = '';
            $maxValue = 1;

            $systemStatus = ($pgpKey && $pgpKey->verified) ? 'secure' : 'warning';
            $systemStatusText = $systemStatus === 'secure' ? 'Secure Node' : 'Security Setup Incomplete';

            if ($showFullInfo) {
                $windowStart = now()->subDays(14);
                $previousWindowStart = now()->subDays(28);

                $ordersQuery = Order::query()->where('user_id', $user->id);
                $currentOrders = (clone $ordersQuery)->where('created_at', '>=', $windowStart)->count();
                $previousOrders = (clone $ordersQuery)->whereBetween('created_at', [$previousWindowStart, $windowStart])->count();

                $currentSpend = (clone $ordersQuery)->where('is_paid', true)->where('created_at', '>=', $windowStart)->sum('total');
                $previousSpend = (clone $ordersQuery)->where('is_paid', true)->whereBetween('created_at', [$previousWindowStart, $windowStart])->sum('total');

                $wishlistCount = Wishlist::where('user_id', $user->id)->count();
                $conversationCount = $user->conversations()->count();

                $openDisputes = Dispute::where('status', Dispute::STATUS_ACTIVE)
                    ->whereHas('order', function ($query) use ($user) {
                        $query->where('user_id', $user->id)->orWhere('vendor_id', $user->id);
                    })
                    ->count();

                $buildTrend = function (float|int $current, float|int $previous): array {
                    if ($previous <= 0 && $current <= 0) {
                        return ['text' => 'No change', 'direction' => 'flat'];
                    }
                    if ($previous <= 0) {
                        return ['text' => 'New activity', 'direction' => 'up'];
                    }

                    $delta = (($current - $previous) / $previous) * 100;
                    if ($delta > 0) {
                        return ['text' => '+' . number_format($delta, 1) . '%', 'direction' => 'up'];
                    }
                    if ($delta < 0) {
                        return ['text' => '-' . number_format(abs($delta), 1) . '%', 'direction' => 'down'];
                    }
                    return ['text' => '0.0%', 'direction' => 'flat'];
                };

                $metrics = [
                    [
                        'label' => 'Order (14d)',
                        'value' => number_format($currentOrders),
                        'context' => 'vs previous 14 days',
                        'trend' => $buildTrend($currentOrders, $previousOrders),
                        'status' => 'neutral',
                    ],
                    [
                        'label' => 'Spend (14d)',
                        'value' => '$' . number_format($currentSpend, 2),
                        'context' => 'Paid order volume',
                        'trend' => $buildTrend($currentSpend, $previousSpend),
                        'status' => 'positive',
                    ],
                    [
                        'label' => 'Open Disputes',
                        'value' => number_format($openDisputes),
                        'context' => $openDisputes > 0 ? 'Action recommended' : 'All clear',
                        'trend' => ['text' => $openDisputes > 0 ? 'Attention' : 'Healthy', 'direction' => $openDisputes > 0 ? 'down' : 'up'],
                        'status' => $openDisputes > 0 ? 'warning' : 'positive',
                    ],
                    [
                        'label' => 'Active Conversations',
                        'value' => number_format($conversationCount),
                        'context' => 'Message channels',
                        'trend' => ['text' => $conversationCount > 0 ? 'Live' : 'Idle', 'direction' => $conversationCount > 0 ? 'up' : 'flat'],
                        'status' => 'neutral',
                    ],
                ];

                $orderEvents = Order::where('user_id', $user->id)
                    ->latest()
                    ->take(6)
                    ->get(['id', 'status', 'total', 'created_at'])
                    ->map(function ($order) {
                        return [
                            'type' => 'Order',
                            'title' => 'Order ' . substr((string) $order->id, 0, 8),
                            'meta' => ucfirst(str_replace('_', ' ', (string) $order->status)) . ' • $' . number_format((float) $order->total, 2),
                            'created_at' => $order->created_at,
                        ];
                    });

                $messageEvents = Message::conversation()
                    ->where(function ($query) use ($user) {
                        $query->where('user_id_1', $user->id)->orWhere('user_id_2', $user->id);
                    })
                    ->latest('last_message_at')
                    ->take(6)
                    ->get(['id', 'last_message_at', 'created_at'])
                    ->map(function ($conversation) {
                        $timestamp = $conversation->last_message_at ?: $conversation->created_at;
                        return [
                            'type' => 'Message',
                            'title' => 'Conversation ' . substr((string) $conversation->id, 0, 8),
                            'meta' => 'Recent activity detected',
                            'created_at' => $timestamp,
                        ];
                    });

                $recentEvents = $recentEvents
                    ->merge($orderEvents)
                    ->merge($messageEvents)
                    ->sortByDesc('created_at')
                    ->take(10)
                    ->values();

                $liveActivityCount = $recentEvents->filter(function ($event) {
                    return $event['created_at'] && $event['created_at']->gte(now()->subDay());
                })->count();

                $revenueByDayRaw = Order::where('user_id', $user->id)
                    ->where('is_paid', true)
                    ->where('created_at', '>=', now()->subDays(6)->startOfDay())
                    ->selectRaw('DATE(created_at) as day, SUM(total) as total')
                    ->groupBy(DB::raw('DATE(created_at)'))
                    ->orderBy('day')
                    ->get()
                    ->keyBy('day');

                $chartSeries = collect(range(6, 0))
                    ->map(function ($offset) use ($revenueByDayRaw) {
                        $day = now()->subDays($offset)->toDateString();
                        return [
                            'label' => now()->subDays($offset)->format('M d'),
                            'value' => (float) ($revenueByDayRaw[$day]->total ?? 0),
                        ];
                    })
                    ->values();

                $maxValue = max(1, $chartSeries->max('value'));
                $chartPoints = $chartSeries->values()->map(function ($point, $index) use ($maxValue, $chartSeries) {
                    $x = $chartSeries->count() > 1 ? (100 / ($chartSeries->count() - 1)) * $index : 0;
                    $y = 100 - (($point['value'] / $maxValue) * 100);
                    return round($x, 2) . ',' . round($y, 2);
                })->implode(' ');
            }

            return view('dashboard', compact(
                'user',
                'profile',
                'pgpKey',
                'userRole',
                'isOwnProfile',
                'showFullInfo',
                'description',
                'metrics',
                'recentEvents',
                'liveActivityCount',
                'wishlistCount',
                'chartSeries',
                'chartPoints',
                'maxValue',
                'systemStatus',
                'systemStatusText'
            ));

        } catch (Exception $e) {
            Log::error('Error loading dashboard: ' . $e->getMessage(), ['user_id' => Auth::id()]);
            return redirect()->route('home')->with('error', 'An error occurred while loading the dashboard. Please try again.');
        }
    }

    private function determineUserRole(User $user): string
    {
        if ($user->hasRole('admin') && $user->hasRole('vendor')) {
            return 'Admin & Vendor';
        } elseif ($user->hasRole('admin')) {
            return 'Administrator';
        } elseif ($user->hasRole('vendor')) {
            return 'Vendor';
        } else {
            return 'Buyer';
        }
    }
}
