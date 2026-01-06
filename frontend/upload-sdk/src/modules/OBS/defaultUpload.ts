import mime from "mime"
import { InitException, InitExceptionCode } from "../../Exception/InitException"
import { UploadException, UploadExceptionCode } from "../../Exception/UploadException"
import type { PlatformRequest, PlatformSimpleUploadOption } from "../../types"
import { PlatformType } from "../../types"
import type { TOS } from "../../types/TOS"
import { parseExtname } from "../../utils/regExpUtil"
import { request } from "../../utils/request"
import { normalizeSuccessResponse } from "../../utils/response"
import type { OBS } from "../../types/OBS"

/**
 * @description: Simple upload POST request
 * @param {File | Blob} file File
 * @param {String} key File name
 * @param {TOS.AuthParams} params Upload credential info
 * @param {PlatformSimpleUploadOption} option Configuration field
 */
const defaultUpload: PlatformRequest<OBS.AuthParams, PlatformSimpleUploadOption> = (
	file,
	key,
	params,
	option,
) => {
	const { AccessKeyId, policy, signature, host, dir, "content-type": contentType } = params

	if (!policy || !AccessKeyId || !signature || !host || !dir) {
		throw new InitException(
			InitExceptionCode.MISSING_CREDENTIALS_PARAMS_FOR_UPLOAD,
			"volcEngine",
			"policy",
			"AccessKeyId",
			"signature",
			"host",
			"dir",
		)
	}

	// Huawei Cloud PostObject upload limit 5GB
	if (file?.size > 5 * 1024 * 1024 * 1024) {
		throw new InitException(InitExceptionCode.UPLOAD_FILE_TO_BIG, key)
	}

	const combinedKey = `${dir}${key}`

	// Wrap the FormData object
	const formData = new FormData()
	formData.append("key", combinedKey)

	// If the backend returns a contentType check such as 'image/' meaning it must start with "image/"
	if (contentType && contentType !== "") {
		// Get file mime
		const fileMimeType = mime.getType(parseExtname(key))
		formData.append("Content-Type", fileMimeType || contentType)
	}

	formData.append("policy", policy)
	formData.append("AccessKeyId", AccessKeyId)
	formData.append("signature", signature)
	formData.append("file", file)

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
		return normalizeSuccessResponse(combinedKey, PlatformType.OBS, res.headers)
	})
}

export default defaultUpload




