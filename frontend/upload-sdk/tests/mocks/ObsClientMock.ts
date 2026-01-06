// Mock OBS client
class ObsClientMock {
	accessKeyId: string

	secretAccessKey: string

	securityToken?: string

	server: string

	constructor(options: any) {
		this.accessKeyId = options.access_key_id
		this.secretAccessKey = options.secret_access_key
		this.securityToken = options.security_token
		this.server = options.server
	}

	// Mock method for initializing multipart upload
	initiateMultipartUpload(params: any, callback: any) {
		const response = {
			InterfaceResult: {
				RequestId: "test-request-id",
				UploadId: "test-upload-id",
			},
			CommonMsg: {
				Status: 200,
				Message: "OK",
			},
		}

		setTimeout(() => {
			callback(null, response)
		}, 10)
	}

	// Mock method for uploading parts
	uploadPart(params: any, callback: any) {
		const response = {
			InterfaceResult: {
				ETag: '"test-etag"',
			},
			CommonMsg: {
				Status: 200,
				Message: "OK",
			},
		}

		setTimeout(() => {
			callback(null, response)
		}, 10)
	}

	// Mock method for completing multipart upload
	completeMultipartUpload(params: any, callback: any) {
		const response = {
			InterfaceResult: {
				Bucket: params.Bucket,
				Key: params.Key,
				ETag: '"test-etag-final"',
				Location: `http://${params.Bucket}.${this.server}/${params.Key}`,
			},
			CommonMsg: {
				Status: 200,
				Message: "OK",
			},
		}

		setTimeout(() => {
			callback(null, response)
		}, 10)
	}

	// Mock method for canceling multipart upload
	abortMultipartUpload(params: any, callback: any) {
		const response = {
			CommonMsg: {
				Status: 204,
				Message: "No Content",
			},
		}

		setTimeout(() => {
			callback(null, response)
		}, 10)
	}

	// Mock method for standard upload
	putObject(params: any, callback: any) {
		const response = {
			InterfaceResult: {
				ETag: '"test-etag"',
			},
			CommonMsg: {
				Status: 200,
				Message: "OK",
			},
		}

		setTimeout(() => {
			callback(null, response)
		}, 10)
	}
}

export default ObsClientMock




