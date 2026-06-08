# Design System Guard

This project uses a baseline-driven design-system guard to prevent new visual drift.

## What It Checks
- New inline styles in Blade views (`style="..."`)
- New custom hex color usage
- New gradient usage
- New CSS selector variants (warning only)

## Commands
```bash
npm run design:guard
```

```bash
npm run design:baseline
```

## Build Integration
`npm run build` runs `design:guard` before Vite build and fails on new token violations.

## Baseline Files
Baselines are stored in:
- `.design-system-baseline/inline_styles.txt`
- `.design-system-baseline/custom_hex.txt`
- `.design-system-baseline/gradients.txt`
- `.design-system-baseline/selectors.txt`

Update baselines only when intentionally accepting visual-system changes.
