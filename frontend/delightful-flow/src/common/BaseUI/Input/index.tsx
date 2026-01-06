import { Input, InputProps } from "antd"
import { GroupProps, TextAreaProps } from "antd/es/input"
import { SearchProps } from "antd/lib/input"
import React, { forwardRef } from "react"
import { InputGlobalStyle } from "./style"

interface DelightfulInputProps extends InputProps {}

const DelightfulInput: any = forwardRef((props: DelightfulInputProps, ref: any) => {
	return (
		<>
			<InputGlobalStyle />
			<Input {...props} className={`nodrag ${props.className}`} ref={ref} />
		</>
	)
})

DelightfulInput.TextArea = forwardRef((props: TextAreaProps, ref: any) => (
	<Input.TextArea {...props} className={`${props.className}`} ref={ref} />
))
DelightfulInput.Search = forwardRef((props: SearchProps, ref: any) => (
	<Input.Search {...props} className={`${props.className}`} ref={ref} />
))
DelightfulInput.Group = forwardRef((props: GroupProps, ref: any) => (
	<Input.Group {...props} className={`${props.className}`} />
))
DelightfulInput.Password = forwardRef((props: DelightfulInputProps, ref: any) => (
	<Input.Password {...props} className={`${props.className}`} ref={ref} />
))

export default DelightfulInput

