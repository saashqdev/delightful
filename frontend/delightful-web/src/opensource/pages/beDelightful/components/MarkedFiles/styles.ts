import { createStyles } from "antd-style"

export const useStyles = createStyles(({ token }) => ({
	batchOperationButton: {
		padding: "0px 12px",
		gap: 4,
	},
	starIcon: {
		color: token.delightfulColorScales.yellow[5],
	},
	starIconButton: {
		display: "inline-flex",
		alignItems: "center",
		justifyContent: "center",
	},
	topicButton: {
		padding: 0,
	},
	pagination: {
		margin: "16px 0 0 0 !important",
	},
	operationButton: {
		padding: 0,
	},
	table: {
		"& .delightful-table-thead > tr > th": {
			backgroundColor: token.delightfulColorScales.grey[0],
			height: 48,
		},
		"& .delightful-table": {
			borderRadius: 0,
		},
		"& .delightful-table-header": {
			borderRadius: "0px !important",
		},
		"& table": {
			borderRadius: 0,
		},
		":where(.css-dev-only-do-not-override-tifxjm).delightful-table-wrapper .delightful-table-container table>thead>tr:first-child >*:first-child":
			{
				borderTopLeftRadius: 0,
			},
		":where(.css-dev-only-do-not-override-tifxjm).delightful-table-wrapper .delightful-table-container table>thead>tr:first-child >*:last-child":
			{
				borderTopRightRadius: 0,
			},
	},
}))
