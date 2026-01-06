import noDataSvg from "@/assets/favorite/noData.svg"
import { useTranslation } from "react-i18next"
import { useDelightfulEmptyFavorStyle } from "./useDelightfulEmptyFavor"

const NoDataSvgComp = () => {
	const { styles } = useDelightfulEmptyFavorStyle()
	return <img src={noDataSvg} className={styles.img} alt="" />
}
type Props = {
	text?: string
}
export default function DelightfulEmptyFavor({ text }: Props) {
	const { t } = useTranslation()
	const { styles } = useDelightfulEmptyFavorStyle()
	return (
		<div className={styles.noDataContainer}>
			<NoDataSvgComp />
			<div>{text ?? t("favorite.noFavorite", { ns: "interface" })}</div>
		</div>
	)
}
