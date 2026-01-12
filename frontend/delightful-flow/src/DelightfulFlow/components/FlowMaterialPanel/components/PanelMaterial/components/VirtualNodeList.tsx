import React, { memo, useState, useEffect, useRef } from 'react'
import clsx from 'clsx'
import { prefix } from '@/DelightfulFlow/constants'
import styles from '../index.module.less'

interface VirtualNodeListProps {
  items: JSX.Element[]
  itemHeight: number
  overscan?: number
}

const VirtualNodeList = memo(({
  items,
  itemHeight,
  overscan = 5
}: VirtualNodeListProps) => {
  const containerRef = useRef<HTMLDivElement>(null)
  const [visibleRange, setVisibleRange] = useState({ start: 0, end: 20 })
  const [containerHeight, setContainerHeight] = useState(0)

  //  Calculate total height
  const totalHeight = items.length * itemHeight

  //  listen scrolling event, calculate visible range
  useEffect(() => {
    const handleScroll = () => {
      if (!containerRef.current) return

      const { scrollTop, clientHeight } = containerRef.current
      
      //  Calculate start and end index
      const startIndex = Math.max(0, Math.floor(scrollTop / itemHeight) - overscan)
      const endIndex = Math.min(
        items.length - 1,
        Math.ceil((scrollTop + clientHeight) / itemHeight) + overscan
      )

      setVisibleRange({ start: startIndex, end: endIndex })
    }

    //  set container height
    const updateHeight = () => {
      if (containerRef.current) {
        setContainerHeight(containerRef.current.clientHeight)
      }
    }

    const container = containerRef.current
    if (container) {
      container.addEventListener('scroll', handleScroll)
      updateHeight()
      
      // initial calculate
      handleScroll()

      //  listen container size change
      window.addEventListener('resize', updateHeight)
    }

    return () => {
      if (container) {
        container.removeEventListener('scroll', handleScroll)
      }
      window.removeEventListener('resize', updateHeight)
    }
  }, [items.length, itemHeight, overscan])

  //  only render elements inside visible range
  const visibleItems = items.slice(visibleRange.start, visibleRange.end + 1)

  return (
    <div 
      ref={containerRef}
      className={clsx(styles.virtualList, `${prefix}virtual-list`)}
      style={{ overflowY: 'auto', height: '100%' }}
    >
      <div style={{ height: totalHeight, position: 'relative' }}>
        <div 
          style={{ 
            position: 'absolute', 
            top: visibleRange.start * itemHeight,
            width: '100%'
          }}
        >
          {visibleItems}
        </div>
      </div>
    </div>
  )
})

export default VirtualNodeList 
