import DelightfulCode from "@/opensource/components/base/DelightfulCode"
import { CodeRenderProps } from "../types"

const Fallback = (props: CodeRenderProps) => {
	return <DelightfulCode language={props.language} data={props.data} />
}

export default Fallback
