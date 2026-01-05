import { useCallback } from 'react';
import { FLOW_EVENTS, flowEventBus } from '@/common/BaseUI/Select/constants';

/**
 * Hook for emitting flow-design interaction events
 *
 * Use cases:
 * 1. Emit when a node is selected
 * 2. Emit when an edge is selected
 * 3. Emit when the canvas is clicked
 * 4. Emit when flow data changes
 * 5. Emit when layout changes
 * 6. Emit when zoom changes
 */
export const useFlowEventEmitter = () => {
  /**
   * Emit node-selected event
   * @param nodeId Selected node ID
   */
  const emitNodeSelected = useCallback((nodeId?: string) => {
    flowEventBus.emit(FLOW_EVENTS.NODE_SELECTED, nodeId);
  }, []);

  /**
   * Emit edge-selected event
   * @param edgeId Selected edge ID
   */
  const emitEdgeSelected = useCallback((edgeId?: string) => {
    flowEventBus.emit(FLOW_EVENTS.EDGE_SELECTED, edgeId);
  }, []);

  /**
   * Emit canvas-clicked event
   * @param position Click position
   */
  const emitCanvasClicked = useCallback((position?: { x: number, y: number }) => {
    flowEventBus.emit(FLOW_EVENTS.CANVAS_CLICKED, position);
  }, []);

  /**
   * Emit flow-data-updated event
   * @param data Updated flow data
   */
  const emitFlowDataUpdated = useCallback((data?: any) => {
    flowEventBus.emit(FLOW_EVENTS.FLOW_DATA_UPDATED, data);
  }, []);

  /**
   * Emit layout-changed event
   * @param layout Layout payload
   */
  const emitLayoutChanged = useCallback((layout?: any) => {
    flowEventBus.emit(FLOW_EVENTS.LAYOUT_CHANGED, layout);
  }, []);

  /**
   * Emit zoom-changed event
   * @param zoom Zoom value
   */
  const emitZoomChanged = useCallback((zoom?: number) => {
    flowEventBus.emit(FLOW_EVENTS.ZOOM_CHANGED, zoom);
  }, []);

  return {
    emitNodeSelected,
    emitEdgeSelected,
    emitCanvasClicked,
    emitFlowDataUpdated,
    emitLayoutChanged,
    emitZoomChanged
  };
};

export default useFlowEventEmitter; 