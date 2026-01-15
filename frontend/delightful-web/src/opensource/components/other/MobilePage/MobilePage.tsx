import BgHighlightA from "@/assets/resources/background-highlight-1.webp"
import BgHighlightB from "@/assets/resources/background-highlight-2.webp"
import BgHighlightC from "@/assets/resources/background-highlight-3.webp"
import BgHighlightD from "@/assets/resources/background-highlight-4.webp"
import { useStyles } from "./styles"
import {
	Text,
	Logo,
	Name,
	FooterLogo,
	Delightful,
	MacIcon,
	WinIcon,
	IOSIcon,
	AndroidIcon,
} from "./components/image"

function MobilePage() {
	const { styles, cx } = useStyles()

	const size = 1

	return (
		<div className={styles.layout}>
			<div className={styles.grid}>
				<div className={styles.mask} />
				<div className={styles.gridFade} />
				<div className={styles.gridLines} />
			</div>
			<div className={styles.animation}>
				<div className={cx(styles.stepA, styles.step)}>
					<img src={BgHighlightA} alt="" />
				</div>
				<div className={cx(styles.stepB, styles.step)}>
					<img src={BgHighlightB} alt="" />
				</div>
				<div className={cx(styles.stepC, styles.step)}>
					<img src={BgHighlightC} alt="" />
				</div>
				<div className={cx(styles.stepD, styles.step)}>
					<img src={BgHighlightD} alt="" />
				</div>
			</div>
			<div className={styles.wrapper}>
				<div className={styles.header}>
					<Logo size={size} />
					<Text size={size} />
					<Name size={size} />
				</div>
				<div className={styles.desc}>
					Next-generation enterprise-grade AI application innovation engine
				</div>
				<div className={styles.tip}>
					Effortlessly build and operate native AI large-model applications
				</div>
				<div className={styles.menu}>
					<div className={styles.item}>
						<MacIcon />
						<span>Mac Client</span>
						<a href="" className={styles.button}>
							Click to Download
						</a>
					</div>
					<div className={cx(styles.item, styles.itemDisabled)}>
						<WinIcon />
						<span>Windows Client</span>
						<span className={cx(styles.button, styles.buttonDisabled)}>
							Coming soon
						</span>
					</div>
					<div className={cx(styles.item, styles.itemDisabled)}>
						<IOSIcon />
						<span>iPhone Client</span>
						<span className={cx(styles.button, styles.buttonDisabled)}>
							Coming soon
						</span>
					</div>
					<div className={cx(styles.item, styles.itemDisabled)}>
						<AndroidIcon />
						<span>Android Client</span>
						<span className={cx(styles.button, styles.buttonDisabled)}>
							Coming soon
						</span>
					</div>
				</div>
				<div className={styles.footer}>
					<FooterLogo size={1} />
					<span>Copyright © 2025 Letsdelightful.cn All Rights Reserved.</span>
					<span>Guangdong ICP No. 2023088718</span>
				</div>
			</div>

			<Delightful className={styles.watermark} size={1} />
		</div>
	)
}

export default MobilePage
