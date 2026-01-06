import React from "react"
import ButtonDemo from "../components/DelightfulButton"
import SwitchDemo from "../components/DelightfulSwitch"
import CheckFavorDemo from "../components/DelightfulCheckFavor"
import EllipseDemo from "../components/DelightfulEllipse"
import TagDemo from "../components/DelightfulTag"
import ModalDemo from "../components/DelightfulModal"
import TableDemo from "../components/DelightfulTable"
import SearchDemo from "../components/DelightfulSearch"
import SelectDemo from "../components/DelightfulSelect"
import SpinDemo from "../components/DelightfulSpin"
import SegmentedDemo from "../components/DelightfulSegmented"
import CollapseDemo from "../components/DelightfulCollapse"
import SplitterDemo from "../components/DelightfulSplitter"
import MenuDemo from "../components/DelightfulMenu"
import IconDemo from "../components/DelightfulIcon"
import FileIconDemo from "../components/DelightfulFileIcon"
import DropdownDemo from "../components/DelightfulDropdown"
import PageContainerDemo from "../components/DelightfulPageContainer"
import AvatarDemo from "../components/DelightfulAvatar"
import ImageDemo from "../components/DelightfulImage"

export const COMPONENTS = [
	{
		key: "avatar",
		label: "头像 Avatar",
		element: <AvatarDemo />,
	},
	{
		key: "image",
		label: "图片 Image",
		element: <ImageDemo />,
	},
	{
		key: "button",
		label: "按钮 Button",
		element: <ButtonDemo />,
	},
	{
		key: "switch",
		label: "开关 Switch",
		element: <SwitchDemo />,
	},
	{
		key: "checkfavor",
		label: "收藏 CheckFavor",
		element: <CheckFavorDemo />,
	},
	{
		key: "ellipse",
		label: "省略号 Ellipse",
		element: <EllipseDemo />,
	},
	{
		key: "tag",
		label: "标签 Tag",
		element: <TagDemo />,
	},
	{
		key: "modal",
		label: "模态框 Modal",
		element: <ModalDemo />,
	},
	{
		key: "table",
		label: "表格 Table",
		element: <TableDemo />,
	},
	{
		key: "search",
		label: "搜索 Search",
		element: <SearchDemo />,
	},
	{
		key: "select",
		label: "选择器 Select",
		element: <SelectDemo />,
	},
	{
		key: "spin",
		label: "加载 Spin",
		element: <SpinDemo />,
	},
	{
		key: "segmented",
		label: "分段控制器 Segmented",
		element: <SegmentedDemo />,
	},
	{
		key: "collapse",
		label: "折叠面板 Collapse",
		element: <CollapseDemo />,
	},
	{
		key: "splitter",
		label: "分割器 Splitter",
		element: <SplitterDemo />,
	},
	{
		key: "menu",
		label: "菜单 Menu",
		element: <MenuDemo />,
	},
	{
		key: "icon",
		label: "图标 Icon",
		element: <IconDemo />,
	},
	{
		key: "fileicon",
		label: "文件图标 FileIcon",
		element: <FileIconDemo />,
	},
	{
		key: "dropdown",
		label: "下拉菜单 Dropdown",
		element: <DropdownDemo />,
	},
	{
		key: "pagecontainer",
		label: "页面容器 PageContainer",
		element: <PageContainerDemo />,
	},
]
