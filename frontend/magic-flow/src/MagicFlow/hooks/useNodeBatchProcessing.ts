import { useState, useCallback, useRef, useEffect } from 'react'

// Hook to process nodes in batches
export interface BatchProcessingOptions {
  // Nodes per batch
  batchSize?: number
  // Interval between batches (ms)
  interval?: number
  // Auto-stop when all batches finish
  autoStop?: boolean
  // Completion callback
  onComplete?: () => void
  // Progress callback
  onProgress?: (current: number, total: number) => void
}

export const useNodeBatchProcessing = (options: BatchProcessingOptions = {}) => {
  const {
    batchSize = 50,
    interval = 150,
    autoStop = true,
    onComplete,
    onProgress
  } = options

  // Batch processing state
  const [isProcessing, setIsProcessing] = useState(false)
  const [progress, setProgress] = useState({ current: 0, total: 0 })
  
  // Store queued nodes and callbacks
  const nodeQueueRef = useRef<any[]>([])
  const processCallbackRef = useRef<(nodes: any[]) => void>(() => {})
  const intervalIdRef = useRef<any>(null)

  // Stop processing
  const stopProcessing = useCallback(() => {
    if (intervalIdRef.current) {
      clearInterval(intervalIdRef.current)
      intervalIdRef.current = null
    }
    setIsProcessing(false)
  }, [])

  // Cleanup
  useEffect(() => {
    return () => {
      if (intervalIdRef.current) {
        clearInterval(intervalIdRef.current)
      }
    }
  }, [])

  // Process nodes in batches
  const processNodesBatch = useCallback((
    allNodes: any[],
    processCallback: (nodes: any[]) => void,
    customOptions?: Partial<BatchProcessingOptions>
  ) => {
    const mergedOptions = { ...options, ...customOptions }
    const actualBatchSize = mergedOptions.batchSize || batchSize
    const actualInterval = mergedOptions.interval || interval
    
    // Save nodes and callback
    nodeQueueRef.current = [...allNodes]
    processCallbackRef.current = processCallback

    // Calculate total batches
    const totalBatches = Math.ceil(allNodes.length / actualBatchSize)
    
    // No nodes: exit early
    if (allNodes.length === 0) {
      if (mergedOptions.onComplete) {
        mergedOptions.onComplete()
      }
      return
    }
    
    // Single batch: process immediately
    if (allNodes.length <= actualBatchSize) {
      processCallback(allNodes)
      setProgress({ current: 1, total: 1 })
      if (mergedOptions.onProgress) {
        mergedOptions.onProgress(1, 1)
      }
      if (mergedOptions.onComplete) {
        mergedOptions.onComplete()
      }
      return
    }
    
    // Start batch processing
    setIsProcessing(true)
    setProgress({ current: 0, total: totalBatches })
    
    let currentBatch = 0
    
    // Process first batch
    const firstBatch = allNodes.slice(0, actualBatchSize)
    processCallback(firstBatch)
    currentBatch = 1
    setProgress({ current: currentBatch, total: totalBatches })
    if (mergedOptions.onProgress) {
      mergedOptions.onProgress(currentBatch, totalBatches)
    }
    
    // Schedule remaining batches
    intervalIdRef.current = setInterval(() => {
      const start = currentBatch * actualBatchSize
      const end = Math.min(start + actualBatchSize, allNodes.length)
      
      // If all batches processed
      if (currentBatch >= totalBatches) {
        stopProcessing()
        if (mergedOptions.onComplete) {
          mergedOptions.onComplete()
        }
        return
      }
      
      // Build new node list including processed nodes
      const nextBatch = allNodes.slice(0, end)
      processCallback(nextBatch)
      
      // Update progress
      currentBatch++
      setProgress({ current: currentBatch, total: totalBatches })
      if (mergedOptions.onProgress) {
        mergedOptions.onProgress(currentBatch, totalBatches)
      }
      
      // Stop when complete if autoStop is enabled
      if (currentBatch >= totalBatches && autoStop) {
        stopProcessing()
        if (mergedOptions.onComplete) {
          mergedOptions.onComplete()
        }
      }
    }, actualInterval)
    
    return stopProcessing
  }, [options, batchSize, interval, stopProcessing, autoStop])

  return {
    isProcessing,
    progress,
    processNodesBatch,
    stopProcessing
  }
}

export default useNodeBatchProcessing 