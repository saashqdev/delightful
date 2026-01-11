# LoginServiceProvider

## component介绍

LoginServiceProvider 是一个 React Context Provider component，用于管理登录服务的status和逻辑。它主要负责handle不同部署environment（公有云和私有云）下的登录flow，并提供相关的上下文数据和method给子component使用。

## 主要功能

-   管理登录部署environment（公有云/私有云）
-   提供登录服务实例（LoginService）给子component
-   管理集群代码（clusterCode）status
-   提供environment切换能力

## 数据结构

```typescript
interface LoginServiceStore {
	// 登录服务实例
	service: LoginService
	// 当前部署environmentclass型（公有云/私有云）
	deployment: LoginDeployment
	// settings部署environment
	setDeployment: (deployment: LoginDeployment) => void
	// 集群代码（私有部署时使用）
	clusterCode: string | null
	// settings集群代码
	setDeployCode: (clusterCode: string) => void
}
```

## 使用方式

### 基本使用

```tsx
import { LoginServiceProvider } from "./LoginServiceProvider"
import { LoginService } from "@/services/user/LoginService"

// create登录服务实例
const loginService = new LoginService(apis, serviceContainer)

function App() {
	return (
		<LoginServiceProvider service={loginService}>
			{/* 子component可以通过 useLoginServiceContext get上下文 */}
			<YourLoginComponent />
		</LoginServiceProvider>
	)
}
```

### 使用 HOC 包装component

```tsx
import { withLoginService } from "./withLoginService"
import { LoginService } from "@/services/user/LoginService"

// create登录服务实例
const loginService = new LoginService(apis, serviceContainer)

// 使用 HOC 包装component
const WrappedComponent = withLoginService(YourComponent, loginService)

function App() {
	return <WrappedComponent />
}
```

### 在子component中使用上下文

```tsx
import { useLoginServiceContext } from "./useLoginServiceContext"
import { LoginDeployment } from "@/pages/login/constants"

function LoginComponent() {
	const { service, deployment, setDeployment, clusterCode, setDeployCode } =
		useLoginServiceContext()

	const handleLogin = async () => {
		// 使用 service 进行登录operation
		// ...
	}

	const switchToPrivateDeployment = () => {
		setDeployment(LoginDeployment.PrivateDeploymentLogin)
	}

	return (
		<div>
			{/* 根据部署environment渲染不同的登录interface */}
			{deployment === LoginDeployment.PublicDeploymentLogin ? (
				<PublicLoginForm onLogin={handleLogin} />
			) : (
				<PrivateLoginForm
					clusterCode={clusterCode}
					onSetClusterCode={setDeployCode}
					onLogin={handleLogin}
				/>
			)}

			<button onClick={switchToPrivateDeployment}>切换到私有部署登录</button>
		</div>
	)
}
```

## note事项

-   LoginServiceProvider 应当放置在需要访问登录服务的component树的顶部
-   切换部署environment时会自动handle clusterCode 的清除和恢复
-   在私有部署模式下，会从缓存中恢复上次使用的 clusterCode
