# DelightfulModal Delightful Dialog Component

`DelightfulModal` is an enhanced dialog component based on Ant Design's Modal, offering more polished styles and internationalization support.

## Properties

| Property      | Type | Default | Description                          |
| ------------- | ---- | ------- | ------------------------------------ |
| ...ModalProps | -    | -       | Supports all Ant Design Modal props  |

## Static Methods

DelightfulModal provides the same static methods as Ant Design Modal, with added i18n and style optimizations:

-   `DelightfulModal.confirm(config)` - Confirmation dialog
-   `DelightfulModal.info(config)` - Info dialog
-   `DelightfulModal.success(config)` - Success dialog
-   `DelightfulModal.error(config)` - Error dialog
-   `DelightfulModal.warning(config)` - Warning dialog

## Basic Usage

```tsx
import { DelightfulModal } from "@/components/base/DelightfulModal"
import { useState } from "react"

// Basic dialog
const MyComponent = () => {
	const [isModalOpen, setIsModalOpen] = useState(false)

	return (
		<>
			<button onClick={() => setIsModalOpen(true)}>Open dialog</button>
			<DelightfulModal
				title="Dialog Title"
				open={isModalOpen}
				onOk={() => setIsModalOpen(false)}
				onCancel={() => setIsModalOpen(false)}
			>
				<p>This is the dialog content</p>
			</DelightfulModal>
		</>
	)
}

// Using static methods
const showConfirm = () => {
	DelightfulModal.confirm({
		title: "Confirm Action",
		content: "Are you sure you want to proceed?",
		onOk() {
			console.log("User clicked confirm")
		},
		onCancel() {
			console.log("User clicked cancel")
		},
	})
}

// Information
const showInfo = () => {
	DelightfulModal.info({
		title: "Information",
		content: "This is an important message",
	})
}

// Success
const showSuccess = () => {
	DelightfulModal.success({
		title: "Operation Successful",
		content: "Data has been saved successfully",
	})
}

// Error
const showError = () => {
	DelightfulModal.error({
		title: "Operation Failed",
		content: "An error occurred while saving data",
	})
}

// Warning
const showWarning = () => {
	DelightfulModal.warning({
		title: "Warning",
		content: "This action may lead to data loss",
	})
}
```

## Features

1. **Internationalization**: Applies i18n-translated confirm and cancel button text automatically
2. **Optimized styles**: Specific style enhancements for header, content, and footer
3. **Custom icons**: Custom icons for dialog types like info
4. **Improved buttons**: Better styling and interaction for dialog buttons

## When to Use

-   Request user actions without interrupting workflow via page navigation
-   Display system notifications
-   Present feedback in dialog form
-   Require explicit user confirmation

The DelightfulModal component makes dialogs more attractive and user-friendly, with comprehensive internationalization support.
