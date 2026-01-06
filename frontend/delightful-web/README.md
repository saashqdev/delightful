# Delightful

## Development Environment

-   Node.js: latest stable (v18+)
-   pnpm: v9+

## Quick Start

```bash
# Install dependencies
pnpm install

# Start the dev server
pnpm dev

# Build for production
pnpm build

# Run tests
pnpm test
```

## Tech Stack

-   React
-   Vite
-   Zustance
-   SWR
-   Antd

## Development Guidelines

### Code Style

-   ESLint and Prettier enforce formatting
-   Ensure all lint checks pass before committing
-   Components use PascalCase filenames
-   Utility files use camelCase naming

### Component Development

#### Styling

Project styles follow `ant-design@5.x` CSS-in-JS patterns. Read the [`antd-style` guide](https://ant-design.github.io/antd-style/guide/create-styles) before contributing. Follow these project rules:

-   Separate styles from components per view: `useStyle.ts`, `Component.tsx`.
-   Do not use `less`, `styled-components`, or other third-party style plugins/modules.
-   Do not use `classnames`/`clsx`; use `cx` from `useStyle()` for class activation.

#### Shared Components

-   Base components: enhanced Ant Design components at `src/components/base`; prefer using these.
-   Business components: common business components at `src/components/business`.

#### Component Principles

-   Components should be reusable; avoid tight coupling with business logic.
-   Provide complete type definitions.
-   Write usage docs for complex components.
-   Follow the single-responsibility principle.

### Git Workflow

-   Main branch: `released` (TODO)
-   Pre-release branch: `pre-release` (TODO)
-   Test branch: `master`
-   Feature branch: `feature/<feature-name>`
-   Hotfix branch: `hotfix/<issue-desc>`

Commit message format:

```
type(scope): commit message

- type: feat|fix|docs|style|refactor|test|chore
- scope: affected scope
- message: description
```

### Unit Testing

Test framework: [Vitest](https://vitest.dev/)

Beyond feature development, especially for utilities, add thorough unit tests to improve robustness and reduce refactor maintenance cost.

Place test files in a local `__tests__` folder, named `{filename}.test.ts`.

#### Test Conventions

-   Every utility function should have tests.
-   Cover both normal and error flows.
-   Keep test descriptions clear.
-   Organize cases with `describe` and `it`.

### Development Tips

1. Read this document before starting development.
2. Check project docs and dependency docs when issues arise.
3. Define types first when building new features.
4. Self-test before committing; ensure functionality works and tests pass.

## VS Code Extensions (Recommended)

-   i18n Ally
-   Vitest, Vitest Runner
-   Git Graph
