# @delightful/eslint-config

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
    "@delightful/eslint-config": "workspace:*"
  }
}
```

2. Create `eslint.config.js`:

```javascript
// Simplest usage (recommended)
import { createRequire } from 'module';
const require = createRequire(import.meta.url);

// Use the preset directly, one line
const typescriptPreset = require('@delightful/eslint-config/typescript-preset');

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

const baseConfig = require('@delightful/eslint-config/base');
const typescriptConfig = require('@delightful/eslint-config/typescript');

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
const baseConfig = require('@delightful/eslint-config/base');

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

- `@delightful/eslint-config` - Default config
- `@delightful/eslint-config/base` - Base rules
- `@delightful/eslint-config/typescript` - TypeScript rules
- `@delightful/eslint-config/typescript-preset` - TypeScript preset (includes base + TS rules; recommended)
- `@delightful/eslint-config/react` - React rules
- `@delightful/eslint-config/vue` - Vue 3.x rules
- `@delightful/eslint-config/vue2` - Vue 2.x rules
- `@delightful/eslint-config/prettier` - Prettier integration
- `@delightful/eslint-config/jsconfig` - jsconfig.json support
