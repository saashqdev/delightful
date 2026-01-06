import { InitException, InitExceptionCode } from "../../Exception/InitException"
import { UploadException, UploadExceptionCode } from "../../Exception/UploadException"
import type { PlatformRequest, PlatformSimpleUploadOption } from "../../types"
import { PlatformType } from "../../types"
import type { OSS } from "../../types/OSS"
import { request } from "../../utils/request"
import { normalizeSuccessResponse } from "../../utils/response"

/**
 * @description: Default upload
 * @param {File | Blob} file File
 * @param {String} key File name
 * @param {OSS.AuthParams} params Upload credential info
 * @param {PlatformSimpleUploadOption} option Configuration field
 */
export const defaultUpload: PlatformRequest<OSS.AuthParams, PlatformSimpleUploadOption> = (
	file,
	key,
	params,
	option,
) => {
	const { policy, accessid, signature, host, dir, callback } = params

	if (!policy || !accessid || !signature || !host || !dir || !callback) {
		throw new InitException(
			InitExceptionCode.MISSING_CREDENTIALS_PARAMS_FOR_UPLOAD,
			"aliyun",
			"policy",
			"accessid",
			"signature",
			"host",
			"dir",
			"callback",
		)
	}

	// Aliyun PostObject upload limit 5GB
	if (file?.size > 5 * 1024 * 1024 * 1024) {
		throw new InitException(InitExceptionCode.UPLOAD_FILE_TO_BIG, key)
	}

	// Wrap the FormData object
	const formData = new FormData()
	formData.append("key", `${dir}${key}`)
	formData.append("policy", policy)
	formData.append("OSSAccessKeyId", accessid)
	formData.append("signature", signature)
	formData.append("success_action_status", "200")
	formData.append("callback", callback)
	formData.append("file", file)

	// Send request
	return request<OSS.PostResponse>({
		method: "post",
		url: host,
		data: formData,
		headers: option?.headers ? option?.headers : {},
		taskId: option.taskId,
		onProgress: option?.progress ? option.progress : () => {},
		fail: (status, reject) => {
			if (status === 403) {
				reject(new UploadException(UploadExceptionCode.UPLOAD_CREDENTIALS_IS_EXPIRED))
			}
		},
	}).then(({ headers, data: { path } }) => {
		return normalizeSuccessResponse(path, PlatformType.OSS, headers)
	})
}




