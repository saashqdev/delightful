import GlobalErrorBoundary from "@/opensource/components/fallback/GlobalErrorBoundary"
import AppearanceProvider from "@/opensource/providers/AppearanceProvider"
import { BrowserRouter } from "react-router-dom"

/**
 * Used to resolve cases where React nodes from function-based component calls cannot access context
 */
export const DetachComponentProviders = ({ children }: { children: React.ReactNode }) => {
	return (
		<BrowserRouter>
			<AppearanceProvider>
				<GlobalErrorBoundary>
					{/* <AuthenticationProvider> */}
					{children}
					{/* </AuthenticationProvider> */}
				</GlobalErrorBoundary>
			</AppearanceProvider>
		</BrowserRouter>
	)
}
