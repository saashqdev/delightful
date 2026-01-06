# FlowMaterialPanel Performance Optimizations

This document captures performance optimizations applied to the FlowMaterialPanel component to mitigate rendering jank under heavy data loads.

## Completed optimizations

### 1. Leaf component (MaterialItem)
- Wrapped MaterialItem with `React.memo` and a custom comparison function
- Compare only critical props (id, label, desc, etc.) to avoid unnecessary rerenders
- Update only when node data actually changes

### 2. SubGroup component
- Wrapped with `React.memo` and a precise custom comparison
- Track expand/collapse state and skip rendering children while collapsed
- Added `SubGroupItem` wrapper for per-item memoization
- Cache node list data to avoid refetching on every expand
- Wrapped functions with `useCallback` to keep stable references

### 3. PanelMaterial component
- Wrapped with `React.memo` and a custom comparison
- Optimized the MaterialItemFn wrapper with `useCallback`
- Used `useMemo` for lists and groups to reduce recomputation
- Created stable keys to avoid regenerating strings on rerender

### 4. LazySubGroup lazy loading
- Implemented lazy loading via IntersectionObserver
- Render children only when they enter the viewport
- Auto-enable lazy loading for large numbers of subgroups

## Impact
These changes significantly improved rendering performance:
1. **Avoid leaf rerenders**: update only when node data changes
2. **Lower memory use**: no more rendering every component at once
3. **Faster initial paint**: lazy loading improves first render
4. **On-demand rendering**: only expanded components fully render their contents
5. **Stable references**: `useCallback` and `useMemo` keep functions/components stable

## Before vs after

### Before
- Every parent update triggered rerenders of all children
- Collapsed components still rendered their children
- No caching/memoization; functions and computed props recreated each render

### After
- Leaf components rerender only when key data changes
- Collapsed components skip rendering children, saving resources
- Memoization and caching significantly reduce compute and render overhead

## Next steps
1. Consider paginating data loads
2. Keep reducing component complexity; split large components into smaller units
3. Add performance monitoring to capture real-world metrics
4. If issues persist, consider time-slicing (React Concurrent Mode)
