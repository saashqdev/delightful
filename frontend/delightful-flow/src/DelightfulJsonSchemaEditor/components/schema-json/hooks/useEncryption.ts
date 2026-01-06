import { FormItemType } from '@/DelightfulExpressionWidget/types'
import { useGlobal } from '@/DelightfulJsonSchemaEditor/context/GlobalContext/useGlobal'
import { DisabledField } from '@/DelightfulJsonSchemaEditor/types/Schema'
import i18next, { t } from 'i18next'
import { useMemo } from 'react'
import { useTranslation } from 'react-i18next'

type EncryptionProps = {
	value: any
}

export default function useEncryption({ 
	value
}: EncryptionProps) {


	const {
		disableFields
	} = useGlobal()
	
	// Determine whether encryption should be disabled
	const disableEncryption = useMemo(() => {
		const isDisabledEncryption = disableFields.includes(DisabledField.Encryption)
		// Only allow basic value types to be encrypted
		const enableEncryptionTypes = [
			FormItemType.Integer,
			FormItemType.Number,
			FormItemType.String,
		]
		// If a value is already encrypted, disallow encrypting again
		const isEncryption = value?.encryption_value
		return isEncryption || isDisabledEncryption || !enableEncryptionTypes.includes(value?.type)
	}, [value, disableFields])


	/** Tooltip text for the encryption button */
	const encryptionTooltips = useMemo(() => {
		// If already encrypted, do not allow encrypting again
		const isEncryption = value?.encryption_value
		if(isEncryption) return i18next.t("jsonSchema.encryptionDesc", { ns: "delightfulFlow" })
		return ""
	}, [value])

	return {
		disableEncryption,
		encryptionTooltips
	}
}

