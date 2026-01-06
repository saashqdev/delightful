import React from "react"
import { Space } from "antd"
import DelightfulFileIcon from "../../components/DelightfulFileIcon"
import ComponentDemo from "./Container"

const FileIconDemo: React.FC = () => {
	return (
		<div>
			<ComponentDemo
				title="Basic File Icon"
				description="Most basic file icon component"
				code="<DelightfulFileIcon type='pdf' />"
			>
				<Space>
					<DelightfulFileIcon type="pdf" />
					<DelightfulFileIcon type="doc" />
					<DelightfulFileIcon type="xls" />
					<DelightfulFileIcon type="ppt" />
				</Space>
			</ComponentDemo>

			<ComponentDemo
				title="Document Types"
				description="Supports various document types"
				code="type: 'pdf' | 'doc' | 'xls' | 'ppt' | 'txt'"
			>
				<Space wrap>
					<DelightfulFileIcon type="pdf" />
					<DelightfulFileIcon type="doc" />
					<DelightfulFileIcon type="xls" />
					<DelightfulFileIcon type="ppt" />
					<DelightfulFileIcon type="txt" />
					<DelightfulFileIcon type="markdown" />
				</Space>
			</ComponentDemo>

			<ComponentDemo
				title="Media Types"
				description="Supports various media file types"
				code="type: 'image' | 'video' | 'audio'"
			>
				<Space wrap>
					<DelightfulFileIcon type="image" />
					<DelightfulFileIcon type="video" />
					<DelightfulFileIcon type="audio" />
				</Space>
			</ComponentDemo>

			<ComponentDemo
				title="Code Types"
				description="Supports various code file types"
				code="type: 'code' | 'json' | 'xml' | 'html' | 'css' | 'js'"
			>
				<Space wrap>
					<DelightfulFileIcon type="code" />
					<DelightfulFileIcon type="json" />
					<DelightfulFileIcon type="xml" />
					<DelightfulFileIcon type="html" />
					<DelightfulFileIcon type="css" />
					<DelightfulFileIcon type="js" />
				</Space>
			</ComponentDemo>

			<ComponentDemo
				title="Other Types"
				description="Supports other file types"
				code="type: 'folder' | 'zip' | 'link' | 'other'"
			>
				<Space wrap>
					<DelightfulFileIcon type="folder" />
					<DelightfulFileIcon type="zip" />
					<DelightfulFileIcon type="link" />
					<DelightfulFileIcon type="other" />
				</Space>
			</ComponentDemo>

			<ComponentDemo
				title="Different Sizes"
				description="Supports different sized file icons"
				code="size: number | string"
			>
				<Space>
					<DelightfulFileIcon type="pdf" size={16} />
					<DelightfulFileIcon type="pdf" size={24} />
					<DelightfulFileIcon type="pdf" size={32} />
					<DelightfulFileIcon type="pdf" size={48} />
				</Space>
			</ComponentDemo>
		</div>
	)
}

export default FileIconDemo
