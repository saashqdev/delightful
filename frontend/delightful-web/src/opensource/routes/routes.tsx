import type { RouteObject } from "react-router"
import { lazy } from "react"
import { RoutePath } from "@/const/routes"
import BaseLayout from "@/opensource/layouts/BaseLayout"

export function registerRoutes(): Array<RouteObject> {
	/** 404 Page */
	const NotFound = lazy(() => import("@/opensource/pages/notFound"))

	/** SSO module */
	const SSOLayout = lazy(() => import("@/opensource/layouts/SSOLayout"))
	/** SSO */
	const SignIn = lazy(() => import("@/opensource/pages/login/login"))

	/** Chat (new) */
	const ChatNew = lazy(() => import("@/opensource/pages/chatNew"))

	/** Contacts module */
	const ContactsLayout = lazy(() => import("@/opensource/pages/contacts/layouts"))
	/** Contacts */
	const ContactsOrganization = lazy(() => import("@/opensource/pages/contacts/organization"))
	/** Contacts */
	const ContactsFriend = lazy(() => import("@/opensource/pages/contacts/myFriends"))
	/** Contacts */
	const ContactsGroups = lazy(() => import("@/opensource/pages/contacts/myGroups"))
	/** AI Assistant */
	const ContactsAiAssistant = lazy(() => import("@/opensource/pages/contacts/aiAssistant"))

	/** Flow module */
	const FlowLayout = lazy(() => import("@/opensource/pages/flow/layouts"))
	/** Details */
	const FlowDetail = lazy(() => import("@/opensource/pages/flow"))
	/** Explore */
	const FlowExplore = lazy(() => import("@/opensource/pages/explore"))
	/** AI Assist */
	const FlowAgent = lazy(() => import("@/opensource/pages/flow/agent"))
	/** Flow list (Workflows/Subflows/Tools/Knowledge Base) */
	const FlowList = lazy(() => import("@/opensource/pages/flow/list"))
	/** Knowledge base details */
	const FlowKnowledgeDetail = lazy(() => import("@/opensource/pages/flow/knowledge/[id]"))

	/**
	 * @description Vector knowledge base module
	 */
	const VectorKnowledgeLayout = lazy(() => import("@/opensource/pages/vectorKnowledge/layouts"))
	/** Create */
	const VectorKnowledgeCreate = lazy(
		() => import("@/opensource/pages/vectorKnowledge/components/Create"),
	)
	/** Details */
	const VectorKnowledgeDetail = lazy(
		() => import("@/opensource/pages/vectorKnowledge/components/Details"),
	)

	/** Global Settings */
	const Settings = lazy(() => import("../pages/settings/Settings.page"))

	/** Be Delightful - Workspace */
	const BeDelightfulWorkspace = lazy(() => import("@/opensource/pages/beDelightful/pages/Workspace"))

	/** Be Delightful Share (no login required) */
	const BeDelightfulShare = lazy(() => import("@/opensource/pages/share"))

	return [
		{
			path: RoutePath.BeDelightfulShare,
			element: <BeDelightfulShare />,
		},
		{
			path: "/",
			element: <BaseLayout />,
			children: [
				{
					path: "/",
					element: <Settings />,
				},
				{
					path: RoutePath.Chat,
					element: <ChatNew />,
				},
				{
					path: RoutePath.BeDelightful,
					element: <BeDelightfulWorkspace />,
				},
				{
					path: RoutePath.Contacts,
					element: <ContactsLayout />,
					children: [
						{
							path: RoutePath.ContactsOrganization,
							element: <ContactsOrganization />,
						},
						{
							path: RoutePath.ContactsMyFriends,
							element: <ContactsFriend />,
						},
						{
							path: RoutePath.ContactsMyGroups,
							element: <ContactsGroups />,
						},
						{
							path: RoutePath.ContactsAiAssistant,
							element: <ContactsAiAssistant />,
						},
					],
				},
				{
					path: RoutePath.Explore,
					element: <FlowExplore />,
				},
				{
					path: RoutePath.FlowDetail,
					element: <FlowDetail />,
				},
				{
					path: RoutePath.Flow,
					element: <FlowLayout />,
					children: [
						{
							path: RoutePath.AgentList,
							element: <FlowAgent />,
						},
						{
							path: RoutePath.Flows,
							element: <FlowList />,
						},
						{
							path: RoutePath.FlowKnowledgeDetail,
							element: <FlowKnowledgeDetail />,
						},
					],
				},
				{
					path: RoutePath.VectorKnowledge,
					element: <VectorKnowledgeLayout />,
					children: [
						{
							path: RoutePath.VectorKnowledgeCreate,
							element: <VectorKnowledgeCreate />,
						},
						{
							path: RoutePath.VectorKnowledgeDetail,
							element: <VectorKnowledgeDetail />,
						},
					],
				},
				{
					path: RoutePath.Explore,
					element: <FlowExplore />,
				},
				{
					path: RoutePath.Settings,
					element: <Settings />,
				},
			],
		},
		{
			path: RoutePath.Login,
			element: <SSOLayout />,
			children: [
				{
					path: RoutePath.Login,
					element: <SignIn />,
				},
			],
		},
		{
			path: RoutePath.NotFound,
			element: <NotFound />,
		},
	]
}
