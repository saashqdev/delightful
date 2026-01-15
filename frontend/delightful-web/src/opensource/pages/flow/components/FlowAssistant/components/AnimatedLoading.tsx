import { css, keyframes } from "@emotion/css"

// Define animation keyframes
const dotAnimation = keyframes`
  0% { 
    opacity: 0.3;
    transform: translateX(-5px);
  }
  50% { 
    opacity: 1;
    transform: translateX(0);
  }
  100% { 
    opacity: 0.3;
    transform: translateX(5px);
  }
`

// Animated loading styles
const animatedLoadingStyles = {
	container: css`
		margin-top: 12px;
		display: flex;
		align-items: center;
		font-size: 14px;
		color: #1890ff;
		background: rgba(24, 144, 255, 0.05);
		padding: 10px;
		border-radius: 4px;
	`,
	dot: css`
		opacity: 0.3;
		animation: ${dotAnimation} 1.4s ease-in-out infinite;
		margin-right: 4px;
		display: inline-block;
	`,
	dot2: css`
		opacity: 0.3;
		animation: ${dotAnimation} 1.4s ease-in-out infinite;
		animation-delay: 0.1s;
		margin-right: 4px;
		display: inline-block;
	`,
	dot3: css`
		opacity: 0.3;
		animation: ${dotAnimation} 1.4s ease-in-out infinite;
		animation-delay: 0.2s;
		margin-right: 4px;
		display: inline-block;
	`,
	dot4: css`
		opacity: 0.3;
		animation: ${dotAnimation} 1.4s ease-in-out infinite;
		animation-delay: 0.3s;
		margin-right: 4px;
		display: inline-block;
	`,
	dot5: css`
		opacity: 0.3;
		animation: ${dotAnimation} 1.4s ease-in-out infinite;
		animation-delay: 0.4s;
		margin-right: 4px;
		display: inline-block;
	`,
	dot6: css`
		opacity: 0.3;
		animation: ${dotAnimation} 1.4s ease-in-out infinite;
		animation-delay: 0.5s;
		margin-right: 4px;
		display: inline-block;
	`,
	dot7: css`
		opacity: 0.3;
		animation: ${dotAnimation} 1.4s ease-in-out infinite;
		animation-delay: 0.6s;
		margin-right: 4px;
		display: inline-block;
	`,
	dot8: css`
		opacity: 0.3;
		animation: ${dotAnimation} 1.4s ease-in-out infinite;
		animation-delay: 0.7s;
		margin-right: 4px;
		display: inline-block;
	`,
}

// Regular loading container styles
export const loadingContainerStyle = css`
	padding: 10px 0;
	display: flex;
	align-items: center;
	justify-content: flex-start;
`

/**
 * Custom animated loading component
 * Shows animation for "Collecting command data"
 */
const AnimatedLoading = () => {
	return (
		<div className={animatedLoadingStyles.container}>
			<span className={animatedLoadingStyles.dot}>C</span>
			<span className={animatedLoadingStyles.dot2}>o</span>
			<span className={animatedLoadingStyles.dot3}>l</span>
			<span className={animatedLoadingStyles.dot4}>l</span>
			<span className={animatedLoadingStyles.dot5}>e</span>
			<span className={animatedLoadingStyles.dot6}>c</span>
			<span className={animatedLoadingStyles.dot7}>t</span>
			<span className={animatedLoadingStyles.dot8}>.</span>
		</div>
	)
}

export default AnimatedLoading
