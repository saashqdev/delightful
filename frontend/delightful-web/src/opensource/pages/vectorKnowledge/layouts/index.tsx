import { Outlet } from "react-router-dom"
import { RoutePath } from "@/const/routes"
import type { PropsWithChildren } from "react"
import { Suspense, useEffect } from "react"
import DelightfulSpin from "@/opensource/components/base/DelightfulSpin"
import { useLocation } from "react-router"
import { useNavigate } from "@/opensource/hooks/useNavigate"

interface VectorKnowledgeLayoutProps extends PropsWithChildren {}

export default function VectorKnowledgeLayout({ children }: VectorKnowledgeLayoutProps) {
	const navigate = useNavigate()

	const { pathname } = useLocation()

	// If current path is not detail or create page, redirect to create page by default
	useEffect(() => {
		if (
			!pathname.includes(RoutePath.VectorKnowledgeDetail) &&
			!pathname.includes(RoutePath.VectorKnowledgeCreate)
		) {
			navigate(RoutePath.VectorKnowledgeCreate)
		}
	}, [pathname, navigate])

	return <Suspense fallback={<DelightfulSpin />}>{children || <Outlet />}</Suspense>
}
