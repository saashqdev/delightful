# ComponentRender

ComponentRender is a factory-pattern implementation for dynamically rendering components. It allows developers to register, retrieve, and render components at runtime.

## Features

- Dynamic component registration and management
- Component rendering by component name
- Support for lazy-loaded components
- Default Fallback component for unregistered components
- Type-safe component rendering

## Usage

### 1. Basic usage

```tsx
import ComponentRender from '@/opensource/components/ComponentRender';

function MyPage() {
  return (
    <ComponentRender 
      componentName="OrganizationList"
      // You can pass any props required by the component
      prop1="value1"
      prop2="value2"
    />
  );
}
```

### 2. Dynamically register a component

You can use `ComponentFactory` to dynamically register new components:

```tsx
import ComponentFactory from '@/opensource/components/ComponentRender/ComponentFactory';

// Define your component
const MyCustomComponent = ({ title, content }) => (
  <div>
    <h2>{title}</h2>
    <p>{content}</p>
  </div>
);

// Register a single component
ComponentFactory.registerComponent('MyCustomComponent', MyCustomComponent);

// Use the registered component
function MyPage() {
  return (
    <ComponentRender 
      componentName="MyCustomComponent" 
      title="Hello"
      content="This is my custom component"
    />
  );
}
```

### 3. Register multiple components

```tsx
import ComponentFactory from '@/opensource/components/ComponentRender/ComponentFactory';

// Prepare multiple components
const components = {
  Component1: () => <div>Component 1</div>,
  Component2: () => <div>Component 2</div>,
  Component3: () => <div>Component 3</div>,
};

// Register components in bulk
ComponentFactory.registerComponents(components);
```

### 4. Unregister components

```tsx
// Unregister a single component
ComponentFactory.unregisterComponent('MyCustomComponent');

// Unregister multiple components
ComponentFactory.unregisterComponents(['Component1', 'Component2']);
```

### 5. Customize component types

If you need to add a new component type, extend the `DefaultComponentsProps` interface:

```tsx
// Extend the interface in your file
declare module '@/opensource/components/ComponentRender/config/defaultComponents' {
  export interface DefaultComponentsProps {
    // Add a new component type
    MyNewComponent: {
      title: string;
      description: string;
      onClick: () => void;
    };
  }
}

// Then register the component
const MyNewComponent: React.FC<{ title: string; description: string; onClick: () => void }> = (props) => {
  // Implementation
};

ComponentFactory.registerComponent('MyNewComponent', MyNewComponent);
```

### 6. Pass children

ComponentRender also supports passing children:

```tsx
<ComponentRender componentName="ContainerComponent">
  <div>These are child contents</div>
  <button>Child button</button>
</ComponentRender>
```

## Best Practices

1. **Component lazy-loading**: For large components, wrap with `React.lazy()` before registering to optimize initial load performance.

2. **Type safety**: Always extend the `DefaultComponentsProps` interface to maintain type safety.

3. **Registration timing**: Register global components during app initialization (e.g., in the entry file or layout components).

4. **Naming conventions**: Use meaningful, unique component names to avoid conflicts.

5. **Error handling**: Provide dedicated error boundaries for critical components, not just the default Fallback component.

## Notes

- All components rendered via ComponentRender are wrapped with `<Suspense fallback={null}>`. For lazy-loaded components, `null` is displayed until the component finishes loading.
- Unregistered component names will render the default Fallback component (displaying "Component UnRegistered").
- Component registration is global; ensure component names are unique across the application.