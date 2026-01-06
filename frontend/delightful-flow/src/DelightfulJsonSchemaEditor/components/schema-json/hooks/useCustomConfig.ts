/**
 * Return configurable options based on user-supplied field configs and special control types
 */
import { FormItemType } from '@/DelightfulExpressionWidget/types'
import React, { useContext, useMemo } from 'react'
import { getDefaultBooleanConstantSource } from '../schema-item/constants'
import { useGlobal } from '@/DelightfulJsonSchemaEditor/context/GlobalContext/useGlobal'
import { EditorContext } from '../../editor'
import _ from 'lodash'
import { SchemaValueSplitor } from '@/DelightfulJsonSchemaEditor/constants'

type CustomConfigProps = {
	value: any
	name: string
	type: string
}

export default function useCustomConfig({ value, name, type }: CustomConfigProps) {
	const {
		allowOperation,
		customFieldsConfig,
		onlyExpression,
		allowAdd
	} = useGlobal()

	const { expressionSource } = useContext(EditorContext)
	
	const expressionSourceWithDefaultOptions = useMemo(() => {
		if (value?.type === FormItemType.Boolean) {
			const booleanConstants = getDefaultBooleanConstantSource()
			return [...booleanConstants, ...(expressionSource || [])]
		}
		return expressionSource
	}, [expressionSource, value])

	const _onlyExpression = useMemo(() => {
		const result = value?.type === FormItemType.Boolean || onlyExpression
		if (!customFieldsConfig || !_.has(customFieldsConfig, [name, "onlyExpression"]))
			return result
		return _.get(customFieldsConfig, [name, "onlyExpression"])
	}, [value, customFieldsConfig])

	const _allowOperation = useMemo(() => {
		if (!customFieldsConfig || !_.has(customFieldsConfig, [name, "allowOperation"]))
			return allowOperation
		return _.get(customFieldsConfig, [name, "allowOperation"])
	}, [allowOperation, customFieldsConfig])

	
	const _allowAdd = useMemo(() => {
		if (!customFieldsConfig || !_.has(customFieldsConfig, [name, "allowAdd"]))
			return allowAdd
		return _.get(customFieldsConfig, [name, "allowAdd"])
	}, [allowAdd, customFieldsConfig])


	const _constantDataSource = useMemo(() => {
		if (_.has(customFieldsConfig, [name, "constantsDataSource"])) {
			return _.get(customFieldsConfig, [name, "constantsDataSource"])
		}
		return []
	}, [customFieldsConfig])
	
	// Whether child member types can be added
	const canAddSubFields = useMemo(() => {
		// All array and object types can add children
		return (
			type?.startsWith?.(`${FormItemType.Array}`) ||
			type === FormItemType.Object
		)
	}, [type])


  return {
	expressionSourceWithDefaultOptions,
	_onlyExpression,
	_allowOperation,
	canAddSubFields,
	_constantDataSource,
	_allowAdd
  }
}
