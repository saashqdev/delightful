import React from "react"
import DelightfulPageContainer from "../../components/DelightfulPageContainer"
import ComponentDemo from "./Container"
import { Button } from "antd"

const PageContainerDemo: React.FC = () => {
	return (
		<div>
			<ComponentDemo
				title="Basic Page Container"
				description="Most basic page container component"
				code="<DelightfulPageContainer title='Page Title'><div>Content</div></DelightfulPageContainer>"
			>
				<DelightfulPageContainer title="Page Title">
					<p>This is the content area of the page container.</p>
					<p>Can contain any React components and content.</p>
				</DelightfulPageContainer>
			</ComponentDemo>

			<ComponentDemo title="With Subtitle" description="Page container with subtitle support" code="subTitle">
				<DelightfulPageContainer title="Page Title" extra={<div>Page Subtitle</div>}>
					<p>This is a page container with subtitle.</p>
				</DelightfulPageContainer>
			</ComponentDemo>

			<ComponentDemo title="With Tabs" description="Page container with tabs support" code="tabList">
				<DelightfulPageContainer
					title="Page Title"
					tabList={[
						{ key: "tab1", tab: "Tab 1" },
						{ key: "tab2", tab: "Tab 2" },
						{ key: "tab3", tab: "Tab 3" },
					]}
				>
					<p>This is a page container with tabs.</p>
				</DelightfulPageContainer>
			</ComponentDemo>

			<ComponentDemo title="With Action Buttons" description="Page container with action buttons support" code="extra">
				<DelightfulPageContainer
					title="Page Title"
					extra={[
						<Button key="1" style={{ marginRight: 8 }}>
							Action 1
						</Button>,
						<Button key="2" type="primary">
							Action 2
						</Button>,
					]}
				>
					<p>This is a page container with action buttons.</p>
				</DelightfulPageContainer>
			</ComponentDemo>
		</div>
	)
}

export default PageContainerDemo
