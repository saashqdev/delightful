import mime from "mime"
import { InitException, InitExceptionCode } from "../../Exception/InitException"
import { UploadException, UploadExceptionCode } from "../../Exception/UploadException"
import type { PlatformRequest, PlatformSimpleUploadOption } from "../../types"
import { PlatformType } from "../../types"
import type { TOS } from "../../types/TOS"
import { parseExtname } from "../../utils/regExpUtil"
import { request } from "../../utils/request"
import { normalizeSuccessResponse } from "../../utils/response"

/**
 * @description: Simple upload POST request
 * @param {File | Blob} file File
 * @param {String} key File name
 * @param {TOS.AuthParams} params Upload credential info
 * @param {PlatformSimpleUploadOption} option Configuration field
 */
const defaultUpload: PlatformRequest<TOS.AuthParams, PlatformSimpleUploadOption> = (
	file,
	key,
	params,
	option,
) => {
	const { policy, host, dir, content_type: contentType } = params

	if (
		!policy ||
		!params["x-tos-signature"] ||
		!params["x-tos-credential"] ||
		!host ||
		!dir ||
		// !params["x-tos-callback"] ||
		!params["x-tos-algorithm"]
	) {
		throw new InitException(
			InitExceptionCode.MISSING_CREDENTIALS_PARAMS_FOR_UPLOAD,
			"volcEngine",
			"policy",
			"x-tos-signature",
			"x-tos-credential",
			"host",
			"dir",
			// "x-tos-callback",
			"x-tos-algorithm",
		)
	}

	// Volcengine PostObject upload limit 5GB
	if (file?.size > 5 * 1024 * 1024 * 1024) {
		throw new InitException(InitExceptionCode.UPLOAD_FILE_TO_BIG, key)
	}

	const combinedKey = `${dir}${key}`
	// Wrap the FormData object
	const formData = new FormData()
	// If the backend returns a contentType check such as 'image/' meaning it must start with "image/"
	if (contentType) {
		// Get file mime
		const fileMimeType = mime.getType(parseExtname(key))
		formData.append("Content-Type", fileMimeType || contentType)
	}
	formData.append("x-tos-server-side-encryption", "AES256")
	formData.append("x-tos-algorithm", params["x-tos-algorithm"])
	formData.append("x-tos-date", params["x-tos-date"])
	formData.append("x-tos-credential", params["x-tos-credential"])
	formData.append("policy", policy)
	formData.append("x-tos-signature", params["x-tos-signature"])
	formData.append("key", combinedKey)
	formData.append("file", file)
	// formData.append("x-tos-callback", params["x-tos-callback"])

	// Send request
	return request<TOS.PostResponse>({
		method: "post",
		url: host,
		data: formData,
		headers: option?.headers ? option?.headers : {},
		taskId: option.taskId,
		onProgress: option?.progress ? option.progress : () => {},
		withoutWrapper: true,
		fail: (status, reject) => {
			if (status === 403) {
				reject(new UploadException(UploadExceptionCode.UPLOAD_CREDENTIALS_IS_EXPIRED))
			}
		},
	}).then((res) => {
		return normalizeSuccessResponse(combinedKey, PlatformType.TOS, res.headers)
	})
}

export default defaultUpload




