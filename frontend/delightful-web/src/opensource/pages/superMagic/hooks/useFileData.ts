import {
	downloadFileContent,
	getTemporaryDownloadUrl,
} from "@/opensource/pages/superMagic/utils/api"
import { useEffect, useState } from "react"
import { useDebounceEffect, useDeepCompareEffect } from "ahooks"
export const useFileData = ({
	file_id,
	type = "download",
}: {
	file_id: string
	type?: "download" | "preview"
}) => {
	const [fileData, setFileData] = useState<any>(null)
	useDebounceEffect(
		() => {
			if (!file_id) return
			getTemporaryDownloadUrl({ file_ids: [file_id] }).then((res: any) => {
				downloadFileContent(res[0]?.url).then((data: any) => {
					setFileData(data)
				})
			})
		},
		[file_id],
		{
			wait: 16,
		},
	)

	return { fileData }
}
