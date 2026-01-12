/**
 * Personnel retrieval filter-related state and behavior & components
 */

import DelightfulSelect from "@/common/BaseUI/Select"
import { Flex, Form } from "antd"
import React, { useMemo } from "react"
import styles from "../SearchUsers.module.less"

export enum FilterTypes {
	Any = "any",
	All = "all",
}

export default function useFilters() {
	const FilterTypeSelector = useMemo(() => {
		return (
			<Flex className={styles.filterTypeWrap} align="center" gap={4}>
				<span className={styles.text}>Match</span>
				<Form.Item name="filter_type">
					<DelightfulSelect
						options={[
							{
								label: "All",
								value: FilterTypes.All,
							},
							{
								label: "Any",
								value: FilterTypes.Any,
							},
						]}
					/>
				</Form.Item>
				<span className={styles.text}>Conditions</span>
			</Flex>
		)
	}, [])

	return {
		FilterTypeSelector,
	}
}

