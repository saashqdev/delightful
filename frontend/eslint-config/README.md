# @dtyq/eslint-config

Foolproof ESLint config package with all dependencies bundled—no extra ESLint or plugin installs needed.

## Features

- ✅ Zero config: ready to use out of the box
- ✅ All dependencies included: no extra ESLint-related packages required
- ✅ Multiple presets: supports base, TypeScript, React, Vue, and more
- ✅ Works seamlessly with pnpm workspaces

## Usage in a pnpm workspace

1. Add the dependency in the target package’s `package.json`:

```json
{
  "devDependencies": {
    "@dtyq/eslint-config": "workspace:*"
  }
}
```

2. Create `eslint.config.js`:

```javascript
// Simplest usage (recommended)
import { createRequire } from 'module';
const require = createRequire(import.meta.url);

// Use the preset directly, one line
const typescriptPreset = require('@dtyq/eslint-config/typescript-preset');

export default [
  { ...typescriptPreset },
  // Custom rules (optional)
  {
    files: ['src/**/*.ts'],
    rules: {
      // Custom rules
    }
  }
];
```

```javascript
// Advanced usage (compose multiple configs)
import { createRequire } from 'module';
const require = createRequire(import.meta.url);

const baseConfig = require('@dtyq/eslint-config/base');
const typescriptConfig = require('@dtyq/eslint-config/typescript');

export default [
  { ...baseConfig },
  { ...typescriptConfig },
  // Custom rules
  {
    files: ['src/**/*.ts'],
    rules: {
      // Custom rules
    }
  }
];
```

```javascript
// CommonJS project
const baseConfig = require('@dtyq/eslint-config/base');

module.exports = {
  ...baseConfig,
  // Custom rules
};
```

3. Add a lint script to `package.json`:

```json
{
  "scripts": {
    "lint": "eslint --config eslint.config.js 'src/**/*.{js,ts,tsx}'"
  }
}
```

## Available configs

- `@dtyq/eslint-config` - Default config
- `@dtyq/eslint-config/base` - Base rules
- `@dtyq/eslint-config/typescript` - TypeScript rules
- `@dtyq/eslint-config/typescript-preset` - TypeScript preset (includes base + TS rules; recommended)
- `@dtyq/eslint-config/react` - React rules
- `@dtyq/eslint-config/vue` - Vue 3.x rules
- `@dtyq/eslint-config/vue2` - Vue 2.x rules
- `@dtyq/eslint-config/prettier` - Prettier integration
- `@dtyq/eslint-config/jsconfig` - jsconfig.json support
