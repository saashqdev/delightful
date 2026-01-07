import DelightfulButton from "@/opensource/components/base/DelightfulButton"
import DelightfulSpin from "@/opensource/components/base/DelightfulSpin"
import { useSize } from "ahooks"
import { Col, Empty, Input, Row } from "antd"
import { cx } from "antd-style"
import { memo, useRef, useState } from "react"
import { useStyles as useCommonStyles } from "../FileManager/styles"
import { useStyles } from "./styles"
import useData from "./useData"

export default memo(function DeployedWebsites() {
	const { styles } = useStyles()
	const { styles: commonStyles } = useCommonStyles()
	const containerRef = useRef<HTMLDivElement>(null)
	const containerSize = useSize(containerRef)
	const [searchKeywords, setSearchKeywords] = useState("")
	const { data, loading, setData } = useData({ searchKeywords })

	const content = data.length ? (
		<div className={styles.dataContainer}>
			<Row gutter={[10, 10]}>
				{data.map((item) => {
					return (
						<Col xs={24} sm={12} md={8} lg={6} xxl={4} key={item.id}>
							<div className={styles.item}>
								<div className={styles.itemHeader}>
									<div className={styles.itemName}>{item.name}</div>
									<div className={styles.itemDescription}>{item.description}</div>
									<div className={styles.itemImage}>123</div>
								</div>
								<div className={styles.itemFooter}>
									<DelightfulButton type="link" className={styles.button}>
										Open link
									</DelightfulButton>
									<DelightfulButton type="link" className={styles.button}>
										Set permissions
									</DelightfulButton>
									<DelightfulButton type="link" danger className={styles.button}>
										Delete
									</DelightfulButton>
								</div>
							</div>
						</Col>
					)
				})}
			</Row>
		</div>
	) : (
		<div className={commonStyles.emptyWrapper}>
			<Empty description="No websites created yet" image={Empty.PRESENTED_IMAGE_SIMPLE} />
		</div>
	)

	return (
		<div className={commonStyles.pageContainer}>
			<div className={commonStyles.title}>Created websites</div>
			<div className={commonStyles.description}>Manage the websites generated through Be Delightful</div>
			<div className={commonStyles.formHeader}>
				<Input
					placeholder="Search websites"
					className={commonStyles.searchInput}
					value={searchKeywords}
					onChange={(event) => {
						setSearchKeywords(event.target.value)
					}}
				/>
			</div>
			<div ref={containerRef} className={cx(commonStyles.pageContent, styles.pageContent)}>
				{loading || !containerSize ? (
					<div className={commonStyles.loadingWrapper}>
						<DelightfulSpin />
					</div>
				) : (
					content
				)}
			</div>
		</div>
	)
})
