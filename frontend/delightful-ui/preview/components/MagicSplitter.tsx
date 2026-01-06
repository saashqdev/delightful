import React from "react"
import DelightfulSplitter from "../../components/DelightfulSplitter"
import ComponentDemo from "./Container"

const SplitterDemo: React.FC = () => {
	return (
		<div>
			<ComponentDemo
				title="基础分割器"
				description="最基本的分割器组件"
				code="<DelightfulSplitter><div>左侧</div><div>右侧</div></DelightfulSplitter>"
			>
				<div style={{ border: "1px solid #d9d9d9" }}>
					<DelightfulSplitter>
						<DelightfulSplitter.Panel min={100}>
							<div style={{ padding: 20, background: "#f0f0f0" }}>左侧面板</div>
						</DelightfulSplitter.Panel>
						<DelightfulSplitter.Panel min={100}>
							<div style={{ padding: 20, background: "#e6f7ff" }}>右侧面板</div>
						</DelightfulSplitter.Panel>
					</DelightfulSplitter>
				</div>
			</ComponentDemo>

			<ComponentDemo
				title="垂直分割器"
				description="垂直方向的分割器"
				code="split='horizontal'"
			>
				<div style={{ border: "1px solid #d9d9d9" }}>
					<DelightfulSplitter layout="vertical" style={{ height: 300 }}>
						<DelightfulSplitter.Panel min={100}>
							<div style={{ padding: 20, background: "#f0f0f0", height: "100%" }}>
								上方面板
							</div>
						</DelightfulSplitter.Panel>
						<DelightfulSplitter.Panel min={100}>
							<div style={{ padding: 20, background: "#e6f7ff", height: "100%" }}>
								下方面板
							</div>
						</DelightfulSplitter.Panel>
					</DelightfulSplitter>
				</div>
			</ComponentDemo>

			<ComponentDemo title="组合分割器" description="组合分割器">
				<div style={{ border: "1px solid #d9d9d9" }}>
					<DelightfulSplitter>
						<DelightfulSplitter.Panel min={100}>
							<div style={{ padding: 20, background: "#f0f0f0", height: "100%" }}>
								左侧面板
							</div>
						</DelightfulSplitter.Panel>
						<DelightfulSplitter.Panel min={100}>
							<DelightfulSplitter layout="vertical" style={{ height: 300 }}>
								<DelightfulSplitter.Panel min={100}>
									<div
										style={{
											padding: 20,
											background: "pink",
											height: "100%",
										}}
									>
										上方面板
									</div>
								</DelightfulSplitter.Panel>
								<DelightfulSplitter.Panel min={100}>
									<div
										style={{
											padding: 20,
											background: "#e6f7ff",
											height: "100%",
										}}
									>
										下方面板
									</div>
								</DelightfulSplitter.Panel>
							</DelightfulSplitter>
						</DelightfulSplitter.Panel>
					</DelightfulSplitter>
				</div>
			</ComponentDemo>
		</div>
	)
}

export default SplitterDemo
