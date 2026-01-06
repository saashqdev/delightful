import { InitException, InitExceptionCode } from "../../Exception/InitException"
import { UploadException, UploadExceptionCode } from "../../Exception/UploadException"
import type { PlatformRequest, PlatformSimpleUploadOption } from "../../types"
import type { Kodo } from "../../types/Kodo"
import { request } from "../../utils/request"

/**
 * @description: Simple upload POST request
 * @param {File | Blob} file File
 * @param {String} key File name
 * @param {Kodo.AuthParams} params Upload credential info
 * @param {PlatformSimpleUploadOption} option Configuration field
 */
export const defaultUpload: PlatformRequest<Kodo.AuthParams, PlatformSimpleUploadOption> = (
	file,
	key,
	params,
	option,
) => {
	const { token, dir } = params

	if (!token || !dir) {
		throw new InitException(
			InitExceptionCode.MISSING_CREDENTIALS_PARAMS_FOR_UPLOAD,
			"qiniu",
			"token",
			"dir",
		)
	}

	// Wrap the FormData object
	const formData = new FormData()
	formData.append("key", `${dir}${key}`)
	formData.append("token", token)
	formData.append("file", file)

	// Send request
	return request({
		method: "post",
		url: "https://upload.qiniup.com",
		data: formData,
		headers: option?.headers ? option?.headers : {},
		taskId: option.taskId,
		onProgress: option?.progress ? option.progress : () => {},
		fail: (status, reject) => {
			if (status === 401) {
				reject(new UploadException(UploadExceptionCode.UPLOAD_CREDENTIALS_IS_EXPIRED))
			}
		},
	})
}




