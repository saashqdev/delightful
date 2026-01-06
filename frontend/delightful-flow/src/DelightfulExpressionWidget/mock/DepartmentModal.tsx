import { Input, Modal } from "antd"
import React from "react"
import { Department } from "../components/nodes/LabelDepartmentNames/LabelDepartmentNames"

const DepartmentModalFC = ({
	onChange,
	closeModal,
	isOpen,
}: {
	isOpen: boolean
	closeModal: () => void
	onChange: (departmentNames: Department[]) => void
}) => {
	return (
		<Modal
			open={isOpen}
			onCancel={closeModal}
			title="Department Selector"
			onOk={() => {
				onChange([
					{
						id: "Department",
						name: "Tech Center",
					},
				])
			}}
		>
			<Input />
		</Modal>
	)
}
export default DepartmentModalFC

