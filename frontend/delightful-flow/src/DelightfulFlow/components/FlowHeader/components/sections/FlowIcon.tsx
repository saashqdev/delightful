import clsx from "clsx"
import React, { memo } from "react"
import { useMemoizedFn } from "ahooks"
import { prefix } from "@/DelightfulFlow/constants"
import styles from "../../index.module.less"

interface FlowIconProps {
  showImage: boolean
  icon: string
  defaultImage: string
}

const FlowIcon = memo(({ showImage, icon, defaultImage }: FlowIconProps) => {
  const handleImageError = useMemoizedFn((event) => {
    event.target.onerror = null // 防止死loop
    event.target.src = defaultImage // 替换为默认图片
  })

  if (!showImage || !icon) return null
  
  return (
    <img
      src={icon}
      alt=""
      className={clsx(styles.flowIcon, `${prefix}flow-icon`)}
      onError={handleImageError}
    />
  )
})

export default FlowIcon 
