import React from "react"
import { Space } from "antd"
import DelightfulFileIcon from "../../components/DelightfulFileIcon"
import ComponentDemo from "./Container"

const FileIconDemo: React.FC = () => {
	return (
		<div>
			<ComponentDemo
				title="基础文件图标"
				description="最基本的文件图标组件"
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
				title="文档类型"
				description="支持各种文档类型"
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
				title="媒体类型"
				description="支持各种媒体文件类型"
				code="type: 'image' | 'video' | 'audio'"
			>
				<Space wrap>
					<DelightfulFileIcon type="image" />
					<DelightfulFileIcon type="video" />
					<DelightfulFileIcon type="audio" />
				</Space>
			</ComponentDemo>

			<ComponentDemo
				title="代码类型"
				description="支持各种代码文件类型"
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
				title="其他类型"
				description="支持其他文件类型"
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
				title="不同尺寸"
				description="支持不同尺寸的文件图标"
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
