import type { PropsWithChildren } from "react"
import { useState } from "react"
import { userStore } from "@/opensource/models/user"
import { loginService } from "@/services"
import { useMount, useDebounceFn } from "ahooks"
import { createStyles } from "antd-style"
import DelightfulSpin from "@/opensource/components/base/DelightfulSpin"
import { useClusterCode } from "@/opensource/providers/ClusterProvider"
import { useTranslation } from "react-i18next"

const useStyles = createStyles(({ css, prefixCls }) => {
	return {
		spin: css`
			.${prefixCls}-spin-blur {
				opacity: 1;
			}

			& > div > .${prefixCls}-spin {
				--${prefixCls}-spin-content-height: unset;
				max-height: unset;
			}
		`,
	}
})

/**
 * @description Cluster configuration synchronization
 * @constructor
 */
export function ClusterConfigSyncProvider(props: PropsWithChildren) {
	const { children } = props

	const { styles } = useStyles()

	const [loading, setLoading] = useState(true)

	const { t } = useTranslation("interface")
	const { setClusterCode } = useClusterCode()

	/**
	 * @description Sync environment configuration
	 * @param {string|null} authorization User token
	 */
	const { run: onUserLoginChange } = useDebounceFn(
		async (access_token: string | null) => {
			try {
				if (access_token) {
					const { clusterCode } = await loginService.syncClusterConfig()
					setClusterCode(clusterCode)
				}
			} catch (error) {
				console.error(error)
			} finally {
				setLoading(false)
				// Redirect needs to be removed here
			}
		},
		{ wait: 3000, leading: true, trailing: false },
	)

	// Sync all states on successful login (user info, organization info, environment config, auth code, etc.)
	useMount(() => {
		const { authorization } = userStore.user
		if (authorization) {
			onUserLoginChange(authorization)?.catch(console.error)
		} else {
			setLoading(false)
		}
	})

	return loading ? (
		<DelightfulSpin spinning wrapperClassName={styles.spin} tip={t("spin.loadingCluster")}>
			<div style={{ height: "100vh" }} />
		</DelightfulSpin>
	) : (
		children
	)
}
