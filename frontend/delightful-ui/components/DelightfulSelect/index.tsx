import { IconChevronDown } from "@tabler/icons-react"
import type { SelectProps } from "antd"
import { Select } from "antd"
import DelightfulIcon from "../DelightfulIcon"
import { memo } from "react"

export type DelightfulSelectProps = SelectProps

const suffixIcon = <DelightfulIcon component={IconChevronDown} size={16} />

const DelightfulSelect = memo(({ ...props }: DelightfulSelectProps) => {
	return <Select suffixIcon={suffixIcon} {...props} />
})

DelightfulSelect.displayName = "DelightfulSelect"

export default DelightfulSelect
