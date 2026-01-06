import { useGlobal } from '@/DelightfulJsonSchemaEditor/context/GlobalContext/useGlobal'
import React, { useMemo } from 'react'

type PropertiesLengthProps = {
	prefix: string[]
}

export default function usePropertiesLength({ prefix }: PropertiesLengthProps) {

	const { showTopRow } = useGlobal()
	
	// Count of fields when type=object
	const propertiesLength = useMemo(() => {
		const result = prefix.filter((name) => name !== "properties").length
		return showTopRow ? result + 1 : result
	}, [prefix, showTopRow])


  return {
	propertiesLength
  }
}
