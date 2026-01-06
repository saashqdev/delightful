import React from "react"
import DelightfulSplitter from "../../components/DelightfulSplitter"
import ComponentDemo from "./Container"

const SplitterDemo: React.FC = () => {
	return (
		<div>
			<ComponentDemo
				title="Basic Splitter"
				description="Most basic splitter component"
				code="<DelightfulSplitter><div>Left</div><div>Right</div></DelightfulSplitter>"
			>
				<div style={{ border: "1px solid #d9d9d9" }}>
					<DelightfulSplitter>
						<DelightfulSplitter.Panel min={100}>
							<div style={{ padding: 20, background: "#f0f0f0" }}>Left Panel</div>
						</DelightfulSplitter.Panel>
						<DelightfulSplitter.Panel min={100}>
							<div style={{ padding: 20, background: "#e6f7ff" }}>Right Panel</div>
						</DelightfulSplitter.Panel>
					</DelightfulSplitter>
				</div>
			</ComponentDemo>

			<ComponentDemo
				title="Vertical Splitter"
				description="Splitter in vertical direction"
				code="split='horizontal'"
			>
				<div style={{ border: "1px solid #d9d9d9" }}>
					<DelightfulSplitter layout="vertical" style={{ height: 300 }}>
						<DelightfulSplitter.Panel min={100}>
							<div style={{ padding: 20, background: "#f0f0f0", height: "100%" }}>
								Top Panel
							</div>
						</DelightfulSplitter.Panel>
						<DelightfulSplitter.Panel min={100}>
							<div style={{ padding: 20, background: "#e6f7ff", height: "100%" }}>
								Bottom Panel
							</div>
						</DelightfulSplitter.Panel>
					</DelightfulSplitter>
				</div>
			</ComponentDemo>

			<ComponentDemo title="Combined Splitter" description="Combined splitter">
				<div style={{ border: "1px solid #d9d9d9" }}>
					<DelightfulSplitter>
						<DelightfulSplitter.Panel min={100}>
							<div style={{ padding: 20, background: "#f0f0f0", height: "100%" }}>
								Left Panel
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
										Top Panel
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
										Bottom Panel
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
