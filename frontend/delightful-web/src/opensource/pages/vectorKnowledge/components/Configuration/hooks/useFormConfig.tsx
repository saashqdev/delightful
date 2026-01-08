import { Form, message } from "antd"
import { useMemoizedFn } from "ahooks"
import { useTranslation } from "react-i18next"
import type { FragmentConfig, ConfigFormValues } from "../../../types"
import { DEFAULT_FORM_VALUES, convertBooleanToPreprocessingRules } from "./formUtils"
import { SegmentationMode, ParentBlockMode } from "../../../constant"
import { processConfigSeparators } from "../../../utils"

export function useFormConfig() {
	const { t } = useTranslation("flow")
	const [form] = Form.useForm<ConfigFormValues>()

	// Use Form.useWatch hook to monitor form field changes in real-time
	const segmentMode = Form.useWatch(["fragment_config", "mode"], form)
	const parentBlockType = Form.useWatch(["fragment_config", "parent_child", "parent_mode"], form)

	// Handle segment mode switching
	const handleSegmentModeChange = useMemoizedFn((mode: SegmentationMode) => {
		resetSegmentSettings(
			mode === SegmentationMode.General
				? SegmentationMode.ParentChild
				: SegmentationMode.General,
		)
		form.setFieldValue(["fragment_config", "mode"], mode)
	})

	// Handle parent block type switching
	const handleParentBlockTypeChange = useMemoizedFn((type: ParentBlockMode) => {
		form.setFieldValue(["fragment_config", "parent_child", "parent_mode"], type)
	})

	// Reset segment settings
	const resetSegmentSettings = useMemoizedFn((mode: SegmentationMode) => {
		let newFragmentConfig = {}
		const fragmentConfig = form.getFieldValue(["fragment_config"])
		if (mode === SegmentationMode.General) {
			newFragmentConfig = {
				...fragmentConfig,
				normal: DEFAULT_FORM_VALUES.fragment_config.normal,
			}
		} else {
			newFragmentConfig = {
				...fragmentConfig,
				parent_child: DEFAULT_FORM_VALUES.fragment_config.parent_child,
			}
		}
		form.setFieldsValue({
			fragment_config: newFragmentConfig,
		})
	})

	// Process form data and convert to API-required format
	const processFormData = useMemoizedFn(async () => {
		try {
			// Validate form and get values
			const formValues = await form.validateFields()

			// Extract boolean values and convert to array format required by API
			const { normal, parent_child, ...restFragmentConfig } = formValues.fragment_config
			const {
				replace_spaces: normalReplaceSpaces,
				remove_urls: normalRemoveUrls,
				...restNormal
			} = normal || {}
			const {
				replace_spaces: parentChildReplaceSpaces,
				remove_urls: parentChildRemoveUrls,
				...restParentChild
			} = parent_child || {}

			// Process escape characters in all separators
			const processedConfig = processConfigSeparators({
				normal: restNormal,
				parent_child: restParentChild,
			})

			// Build final object to submit
			const fragmentConfig: FragmentConfig = {
				...restFragmentConfig,
				normal: {
					...processedConfig.normal,
					text_preprocess_rule: convertBooleanToPreprocessingRules(
						normalReplaceSpaces,
						normalRemoveUrls,
					),
				},
				parent_child: {
					...processedConfig.parent_child,
					text_preprocess_rule: convertBooleanToPreprocessingRules(
						parentChildReplaceSpaces,
						parentChildRemoveUrls,
					),
				},
			}

			return {
				...formValues,
				fragment_config: fragmentConfig,
			}
		} catch (error) {
			console.error("Failed to process form data:", error)
			message.error(t("knowledgeDatabase.saveConfigFailed"))
		}
	})

	return {
		form,
		segmentMode,
		parentBlockType,
		handleSegmentModeChange,
		handleParentBlockTypeChange,
		handleSegmentSettingReset: resetSegmentSettings,
		processFormData,
		initialValues: DEFAULT_FORM_VALUES,
	}
}
