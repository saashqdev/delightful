# Multi-Cluster Login Context

## Overview

`ClusterProvider` is a React context provider for managing login state and cluster configuration in a multi-cluster environment. It provides the ability to switch between different cluster environments, ensuring the application can seamlessly connect to the correct service backend.

## Features

-   Manages the currently active cluster code (`clusterCode`)
-   Provides callback mechanism when cluster changes
-   Automatically syncs cluster configuration information
-   Supports switching between cluster environments
-   Automatically retrieves and sets the appropriate cluster configuration after user login

## Component Structure

```
ClusterProvider/
├── ClusterProvider.tsx       # Main Provider component
├── ClusterConfigSyncProvider.tsx  # Cluster configuration sync component
├── cluster.context.store.ts  # Mobx state management
├── hooks/
│   └── useClusterCode.ts     # Custom Hook for components to access cluster code
└── index.ts                  # Export file
```

## Usage

### 1. Wrap in Application Root Component

```tsx
import { ClusterProvider } from "@/opensource/providers/ClusterProvider"

function App() {
	const handleClusterChange = (clusterCode: string) => {
		// Perform necessary operations when cluster changes, such as switching API base URL
		apiClient.setBaseURL(env("API_BASE_URL", false, clusterCode))
	}

	return (
		<ClusterProvider onClusterChange={handleClusterChange}>
			{/* Other application components */}
		</ClusterProvider>
	)
}
```

### 2. Use Cluster Code in Components

```tsx
import { useClusterCode } from "@/opensource/providers/ClusterProvider"

function MyComponent() {
  const { clusterCode, setClusterCode } = useClusterCode()

  // Use current cluster code
  useEffect(() => {
    if (clusterCode) {
      // Perform cluster-dependent operations
    }
  }, [clusterCode])

  // Switch to another cluster
  const switchCluster = (newClusterCode: string) => {
    setClusterCode(newClusterCode)
  }

  return (/* Component content */)
}
```

## How It Works

1. `ClusterProvider` creates a Mobx state store to manage cluster code
2. Provides `onClusterChange` callback that triggers when cluster code changes
3. `ClusterConfigSyncProvider` automatically syncs cluster configuration after user login
4. Use `useClusterCode` Hook to get and set current cluster code

## Integration with Login Flow

After successful user login, the `ClusterConfigSyncProvider` component will automatically:

1. Retrieve user's cluster configuration information from backend API
2. Set the currently active cluster code
3. Trigger `onClusterChange` callback, allowing the application to respond to cluster changes

## Multi-Cluster Switching

When users need to switch between different clusters, they can change the current cluster through the `setClusterCode` function. This triggers the following flow:

1. Update cluster code in internal state store
2. Notify all components using `useClusterCode`
3. Trigger `onClusterChange` callback, allowing the application to respond to changes (e.g., switch API endpoints)

## Notes

-   Ensure `ClusterProvider` is initialized in the correct location, typically in the application's root component
-   Provide an appropriate `onClusterChange` handler function to respond to cluster changes
-   Cluster code state will be lost after application restart unless manually implemented for persistence
