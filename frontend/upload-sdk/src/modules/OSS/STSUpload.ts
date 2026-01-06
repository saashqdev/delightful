import { InitException, InitExceptionCode } from "../../Exception/InitException"
import type { MethodType, PlatformMultipartUploadOption, PlatformRequest } from "../../types"
import { PlatformType } from "../../types"
import type { OSS } from "../../types/OSS"
import { createRequest } from "./utils/helper"
import { request } from "../../utils/request"
import { normalizeSuccessResponse } from "../../utils/response"

/**
 * @description: STS upload
 * @param {File | Blob} file File
 * @param {String} key File name
 * @param {OSS.STSAuthParams} params Upload credential info
 * @param {PlatformMultipartUploadOption} option Configuration field
 */
export const STSUpload: PlatformRequest<OSS.STSAuthParams, PlatformMultipartUploadOption> = async (
	file,
	key,
	params,
	option,
) => {
	const { region, bucket, dir, access_key_secret, access_key_id, sts_token, callback } = params

	if (
		!region ||
		!bucket ||
		!dir ||
		!access_key_secret ||
		!access_key_id ||
		!sts_token ||
		!callback
	) {
		throw new InitException(
			InitExceptionCode.MISSING_CREDENTIALS_PARAMS_FOR_UPLOAD,
			"alien",
			"region",
			"bucket",
			"dir",
			"access_key_secret",
			"access_key_id",
			"sts_token",
			"callback",
		)
	}

	// Aliyun PostObject upload limit 5GB
	if (file?.size > 5 * 1024 * 1024 * 1024) {
		throw new InitException(InitExceptionCode.UPLOAD_FILE_TO_BIG, key)
	}

	const configParams = {
		...params,
		method: <MethodType>"PUT",
		content: file,
		object: `${dir}${key}`,
		accessKeyId: access_key_id,
		accessKeySecret: access_key_secret,
		stsToken: sts_token,
		subRes: "",
	}

	// Send request
	return request<OSS.PutResponse>({
		...createRequest(configParams, option),
		taskId: option.taskId,
		onProgress: option?.progress ? option.progress : () => {},
	}).then(({ headers, data: { path } }) => {
		return normalizeSuccessResponse(path, PlatformType.OSS, headers)
	})
}




