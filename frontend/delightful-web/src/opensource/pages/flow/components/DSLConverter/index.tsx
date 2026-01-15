import type React from "react"
import { useState } from "react"
import { Button, Card, Input, Select, message, Spin, Space, Typography, Tabs } from "antd"
import { IconArrowRight } from "@tabler/icons-react"
import { createStyles } from "antd-style"
import { convertDSLToJSONString, convertJSONStringToDSL } from "../../utils/api/dslConverter"

const { TextArea } = Input
const { Text, Title } = Typography
const { TabPane } = Tabs

// Create styles using antd-style
const useStyles = createStyles(({ css }) => ({
	dslConverter: css`
		padding: 20px;
		max-width: 1200px;
		margin: 0 auto;
	`,
	conversionTypeSelector: css`
		margin: 20px 0;
	`,
	converterContainer: css`
		display: flex;
		flex-direction: row;
		gap: 16px;
		margin-top: 20px;

		@media (max-width: 768px) {
			flex-direction: column;
		}
	`,
	sourceContainer: css`
		flex: 1;
	`,
	resultContainer: css`
		flex: 1;
	`,
	convertButton: css`
		display: flex;
		align-items: center;
		padding: 0 8px;

		@media (max-width: 768px) {
			justify-content: center;
			padding: 16px 0;
		}
	`,
	actions: css`
		margin-top: 16px;
		display: flex;
		justify-content: flex-end;
	`,
}))

/**
 * DSL Converter Component
 * Provides a UI interface for converting between DSL and JSON formats
 */
const DSLConverter: React.FC = () => {
	const { styles } = useStyles()
	const [sourceText, setSourceText] = useState<string>("")
	const [resultText, setResultText] = useState<string>("")
	const [conversionType, setConversionType] = useState<string>("dsl2json")
	const [loading, setLoading] = useState<boolean>(false)
	const [activeTab, setActiveTab] = useState<string>("converter")

	// File upload handler
	const handleFileUpload = (e: React.ChangeEvent<HTMLInputElement>) => {
		const file = e.target.files?.[0]
		if (!file) return

		const reader = new FileReader()
		reader.onload = (event) => {
			const content = event.target?.result as string
			setSourceText(content)
		}
		reader.readAsText(file)
	}

	// Download result
	const handleDownload = () => {
		if (!resultText) {
			message.warning("No content available to download")
			return
		}

		const element = document.createElement("a")
		const fileType = conversionType.includes("dsl2json") ? "json" : "yml"
		const file = new Blob([resultText], { type: "text/plain" })
		element.href = URL.createObjectURL(file)
		element.download = `converted.${fileType}`
		document.body.appendChild(element)
		element.click()
		document.body.removeChild(element)
	}

	// Execute conversion
	const handleConvert = () => {
		if (!sourceText) {
			message.warning("Please input content to convert")
			return
		}

		setLoading(true)
		try {
			let result

			switch (conversionType) {
				case "dsl2json":
					result = convertDSLToJSONString(sourceText)
					break
				case "json2dsl":
					result = convertJSONStringToDSL(sourceText)
					break
				default:
					result = null
			}

			if (result) {
				setResultText(result)
				message.success("Conversion successful")
			} else {
				message.error("Conversion failed")
			}
		} catch (error) {
			console.error("Conversion error:", error)
			message.error(
				`Conversion failed: ${error instanceof Error ? error.message : String(error)}`,
			)
		} finally {
			setLoading(false)
		}
	}

	// Clear content
	const handleClear = () => {
		setSourceText("")
		setResultText("")
	}

	return (
		<div className={styles.dslConverter}>
			<Tabs activeKey={activeTab} onChange={setActiveTab}>
				<TabPane tab="DSL Converter" key="converter">
					<Card>
						<Title level={4}>DSL and JSON Format Conversion</Title>
						<Text>Convert between YAML workflow DSL format and Flow JSON format</Text>

						<div className={styles.conversionTypeSelector}>
							<Select
								value={conversionType}
								onChange={setConversionType}
								options={[
									{ value: "dsl2json", label: "DSL to JSON" },
									{ value: "json2dsl", label: "JSON to DSL" },
								]}
								style={{ width: 200 }}
							/>
						</div>

						<div className={styles.converterContainer}>
							<div className={styles.sourceContainer}>
								<Card title="Source File" bordered={false}>
									<TextArea
										value={sourceText}
										onChange={(e) => setSourceText(e.target.value)}
										placeholder={
											conversionType === "dsl2json"
												? "Please enter YAML format DSL"
												: "Please enter JSON format Flow"
										}
										rows={20}
									/>
									<div className={styles.actions}>
										<Space>
											<input
												type="file"
												id="file-upload"
												accept={
													conversionType === "dsl2json"
														? ".yml,.yaml"
														: ".json"
												}
												onChange={handleFileUpload}
												style={{ display: "none" }}
											/>
											<Button
												onClick={() =>
													document.getElementById("file-upload")?.click()
												}
											>
												Upload File
											</Button>
											<Button onClick={handleClear}>Clear</Button>
										</Space>
									</div>
								</Card>
							</div>

							<div className={styles.convertButton}>
								<Button
									type="primary"
									icon={<IconArrowRight />}
									onClick={handleConvert}
									loading={loading}
								>
									Convert
								</Button>
							</div>

							<div className={styles.resultContainer}>
								<Card title="Conversion Result" bordered={false}>
									<Spin spinning={loading}>
										<TextArea value={resultText} readOnly rows={20} />
									</Spin>
									<div className={styles.actions}>
										<Button
											type="primary"
											onClick={handleDownload}
											disabled={!resultText}
										>
											Download
										</Button>
									</div>
								</Card>
							</div>
						</div>
					</Card>
				</TabPane>

				<TabPane tab="Help Documentation" key="help">
					<Card>
						<Title level={4}>DSL Converter Usage Guide</Title>
						<Title level={5}>DSL to JSON</Title>
						<Text>
							Convert YAML format workflow DSL (YAML format) to Flow format JSON.
							<ul>
								<li>
									Paste YAML format DSL content in the source file area, or upload
									a YAML file
								</li>
								<li>
									Click the &quot;Convert&quot; button to execute the conversion
								</li>
								<li>
									After conversion is complete, you can download the result or
									copy the result
								</li>
							</ul>
						</Text>

						<Title level={5}>JSON to DSL</Title>
						<Text>
							Convert Flow format JSON to YAML format workflow DSL (YAML format).
							<ul>
								<li>
									Paste JSON format Flow content in the source file area, or
									upload a JSON file
								</li>
								<li>
									Click the &quot;Convert&quot; button to execute the conversion
								</li>
								<li>
									After conversion is complete, you can download the result or
									copy the result
								</li>
							</ul>
						</Text>

						<Title level={5}>Precautions</Title>
						<Text>
							<ul>
								<li>
									Some information specific to a certain format may be lost during
									the conversion process
								</li>
								<li>
									Not all node types can be converted perfectly, manual adjustment
									may be needed
								</li>
								<li>
									If the conversion fails, please check if the source file format
									is correct
								</li>
							</ul>
						</Text>
					</Card>
				</TabPane>
			</Tabs>
		</div>
	)
}

export default DSLConverter
