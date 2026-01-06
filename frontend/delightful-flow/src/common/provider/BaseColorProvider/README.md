# Base Color Management

## Distinguish scales and usages

`usages` are business-level variables built on top of `scales`.

## Types

See `src/utils/palettes.ts` for details.

## How to read global base colors

### Access in createStyles

```tsx
const useStyles = createStyles(({ token }) => {
	return {
		main: {
			color: token.delightfulColorUsages.white,
		},
	}
})
```

### Access via hook

```tsx
import { useBaseColor } from "@/components/providers/BaseColorProvider/hooks"

export default function Comp() {
	// ...

	const { colorScales, colorUsages } = useBaseColor()

	// ...
}
```

