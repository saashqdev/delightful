/**
 * Applicable to nodes that do not have step-by-step debugging but have other common headers
 */

import useHeaderRight from "@/opensource/pages/flow/common/hooks/useHeaderRight"

export default function CommonHeaderRight() {
	const { HeaderRight } = useHeaderRight({})

	return HeaderRight
}





