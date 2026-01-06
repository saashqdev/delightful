import { useGlobalContext } from '@/DelightfulExpressionWidget/context/GlobalContext/useGlobalContext'
import { useMemoizedFn, useUpdateEffect } from 'ahooks'
import React from 'react'
import { EXPRESSION_ITEM, LabelTypeMap } from '@/DelightfulExpressionWidget/types'
import { SnowflakeId } from '@/DelightfulExpressionWidget/helpers'


export type EncryptionProps = {
	displayValue: EXPRESSION_ITEM[]
	clearDisplayValues: () => void
	setDisplayValue: React.Dispatch<React.SetStateAction<EXPRESSION_ITEM[]>>
}

export default function useEncryption({
	displayValue,
	clearDisplayValues,
	setDisplayValue
}: EncryptionProps) {

	const { encryption, hasEncryptionValue } = useGlobalContext()

	/**
	 * Handle focus while encrypted: clear existing values
	 */
	const handleFocusWhenEncryption = useMemoizedFn(() => {
		const hasPasswordValue = displayValue?.find?.(v => v.type === LabelTypeMap.LabelPassword)
		if(encryption && hasPasswordValue) {
			clearDisplayValues()
		}
	})

	
	useUpdateEffect(() => {
		// When encrypted and a value exists, show the password node
		if (encryption && hasEncryptionValue) {
			setDisplayValue([
				{
					type: LabelTypeMap.LabelPassword,
					uniqueId: SnowflakeId(),
					value: "",
				},
			])
		}
		
		// When encryption is turned off, remove the password node
		if(!encryption) {
			setDisplayValue(displayValue.filter(v => v.type !== LabelTypeMap.LabelPassword))
		}
	}, [encryption])

	return {
		handleFocusWhenEncryption
	}
}

