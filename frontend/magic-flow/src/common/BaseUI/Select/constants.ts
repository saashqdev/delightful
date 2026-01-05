// Custom flow event names
export const FLOW_EVENTS = {
  // Node selected event
  NODE_SELECTED: 'flow:node-selected',
  // Edge selected event
  EDGE_SELECTED: 'flow:edge-selected',
  // Canvas clicked event
  CANVAS_CLICKED: 'flow:canvas-clicked',
  // Flow data updated event
  FLOW_DATA_UPDATED: 'flow:data-updated',
  // Layout changed event
  LAYOUT_CHANGED: 'flow:layout-changed',
  // Zoom event
  ZOOM_CHANGED: 'flow:zoom-changed',
}

// Event bus helpers
export const flowEventBus = {
  /**
   * Emit an event
   * @param eventName Event name
   * @param detail Event payload
   */
  emit: (eventName: string, detail?: any) => {
    window.dispatchEvent(new CustomEvent(eventName, { detail }))
  },
  
  /**
   * Listen for an event
   * @param eventName Event name
   * @param handler Event handler
   * @returns Cleanup function
   */
  on: (eventName: string, handler: (event: CustomEvent) => void) => {
    const wrappedHandler = (e: Event) => handler(e as CustomEvent)
    window.addEventListener(eventName, wrappedHandler)
    return () => window.removeEventListener(eventName, wrappedHandler)
  },
  
  /**
   * Remove an event listener
   * @param eventName Event name
   * @param handler Event handler
   */
  off: (eventName: string, handler: (event: CustomEvent) => void) => {
    window.removeEventListener(eventName, handler as EventListener)
  }
} 