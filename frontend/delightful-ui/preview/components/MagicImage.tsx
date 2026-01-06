import React from "react"
import { Space } from "antd"
import DelightfulImage from "../../components/DelightfulImage"
import ComponentDemo from "./Container"

const ImageDemo: React.FC = () => {
	return (
		<div>
			<ComponentDemo
				title="Basic Usage"
				description="Supports all native img tag attributes, usage is the same as normal img tag"
				code="src: string"
			>
				<Space wrap>
					<DelightfulImage
						src="https://picsum.photos/200/150?random=1"
						alt="Random Image 1"
						width={200}
						height={150}
						style={{ borderRadius: "8px" }}
					/>
					<DelightfulImage
						src="https://picsum.photos/200/150?random=2"
						alt="Random Image 2"
						width={200}
						height={150}
						style={{ borderRadius: "8px" }}
					/>
					<DelightfulImage
						src="https://picsum.photos/200/150?random=3"
						alt="Random Image 3"
						width={200}
						height={150}
						style={{ borderRadius: "8px" }}
					/>
				</Space>
			</ComponentDemo>

			<ComponentDemo
				title="Error Fallback"
				description="When image fails to load, it will automatically display the image specified by errorSrc"
				code="errorSrc: string"
			>
				<Space wrap>
					<DelightfulImage
						src="invalid-url"
						errorSrc="https://picsum.photos/200/150?random=fallback"
						alt="Error Fallback Example"
						width={200}
						height={150}
						style={{ borderRadius: "8px" }}
					/>
					<DelightfulImage
						src="https://picsum.photos/200/150?random=4"
						errorSrc="https://picsum.photos/200/150?random=fallback2"
						alt="Normal Image"
						width={200}
						height={150}
						style={{ borderRadius: "8px" }}
					/>
				</Space>
			</ComponentDemo>
		</div>
	)
}

export default ImageDemo
