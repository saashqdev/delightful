# DelightfulModal Magic Dialog Component

`DelightfulModal` is an enhanced dialog component based on Ant Design Modal, providing more beautiful styles and internationalization support.

## Properties

| Property      | Type | Default | Description                                  |
| ------------- | ---- | ------- | -------------------------------------------- |
| ...ModalProps | -    | -       | All properties of Ant Design Modal are supported |

## Static Methods

DelightfulModal provides the same static methods as Ant Design Modal, but with added internationalization support and style optimizations:

-   `DelightfulModal.confirm(config)` - Confirmation dialog
-   `DelightfulModal.info(config)` - Information dialog
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
			<button onClick={() => setIsModalOpen(true)}>Open Dialog</button>
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
		content: "Are you sure you want to perform this action?",
		onOk() {
			console.log("User clicked confirm")
		},
		onCancel() {
			console.log("User clicked cancel")
		},
	})
}

// Information prompt
const showInfo = () => {
	DelightfulModal.info({
		title: "Information",
		content: "This is an important message",
	})
}

// Success prompt
const showSuccess = () => {
	DelightfulModal.success({
		title: "Operation Successful",
		content: "Data has been saved successfully",
	})
}

// Error prompt
const showError = () => {
	DelightfulModal.error({
		title: "Operation Failed",
		content: "An error occurred while saving data",
	})
}

// Warning prompt
const showWarning = () => {
	DelightfulModal.warning({
		title: "Warning",
		content: "This operation may result in data loss",
	})
}
```

## Features

1. **Optimized Styles**: Header, content area, and footer all have specific style optimizations
2. **Custom Icons**: Provides custom icons for info type dialogs and others
3. **Button Style Optimization**: Dialog buttons have better styles and interactive experience

## When to Use

-   When you need users to handle transactions without wanting to navigate away and interrupt workflow
-   When you need to display system prompt information
-   When you need to show feedback information in dialog form
-   When you need to confirm user actions

The DelightfulModal component makes your dialogs more beautiful and user-friendly, while providing comprehensive internationalization support.
