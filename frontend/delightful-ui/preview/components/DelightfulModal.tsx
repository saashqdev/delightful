import React, { useState } from "react"
import { Space, Button } from "antd"
import DelightfulModal from "../../components/DelightfulModal"
import DelightfulButton from "../../components/DelightfulButton"
import ComponentDemo from "./Container"

const ModalDemo: React.FC = () => {
	const [isModalOpen1, setIsModalOpen1] = useState(false)
	const [isModalOpen2, setIsModalOpen2] = useState(false)
	const [isModalOpen4, setIsModalOpen4] = useState(false)

	return (
		<div>
			<ComponentDemo
				title="Basic Modal"
				description="Most basic modal component"
				code="<DelightfulModal open={isOpen} onCancel={() => setIsOpen(false)} />"
			>
				<Space>
					<DelightfulButton type="primary" onClick={() => setIsModalOpen1(true)}>
						Open Basic Modal
					</DelightfulButton>
					<DelightfulModal
						title="Basic Modal"
						open={isModalOpen1}
						onCancel={() => setIsModalOpen1(false)}
						onOk={() => setIsModalOpen1(false)}
					>
						<p>This is an example of basic modal content.</p>
						<p>Click OK or Cancel button to close the modal.</p>
					</DelightfulModal>
				</Space>
			</ComponentDemo>

			<ComponentDemo
				title="Different Size Modals"
				description="Supports different sized modals"
				code="width: number | string"
			>
				<Space>
					<DelightfulButton onClick={() => setIsModalOpen2(true)}>Small Size Modal</DelightfulButton>
					<DelightfulModal
						title="Small Size Modal"
						open={isModalOpen2}
						width={400}
						onCancel={() => setIsModalOpen2(false)}
						onOk={() => setIsModalOpen2(false)}
					>
						<p>This is a small size modal with 400px width.</p>
					</DelightfulModal>
				</Space>
			</ComponentDemo>

			<ComponentDemo
				title="Modal without Buttons"
				description="Can hide default OK and Cancel buttons"
				code="footer: null"
			>
				<Space>
					<DelightfulButton onClick={() => setIsModalOpen4(true)}>Modal without Buttons</DelightfulButton>
					<DelightfulModal
						title="Modal without Buttons"
						open={isModalOpen4}
						footer={null}
						onCancel={() => setIsModalOpen4(false)}
					>
						<p>This modal has no default buttons.</p>
						<p>Can only be closed by clicking the close button in the upper right corner or the mask layer.</p>
						<div style={{ textAlign: "right", marginTop: 16 }}>
							<Button onClick={() => setIsModalOpen4(false)}>Custom Close Button</Button>
						</div>
					</DelightfulModal>
				</Space>
			</ComponentDemo>
		</div>
	)
}

export default ModalDemo
