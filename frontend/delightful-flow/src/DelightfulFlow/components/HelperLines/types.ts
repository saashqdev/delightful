/**
 * Viewport transform parameters
 */
export interface ViewportTransform {
  /** x-axis translation */
  x: number;
  /** y-axis translation */
  y: number;
  /** Scale factor */
  zoom: number;
}

/**
 * Helper line configuration options
 */
export interface HelperLinesOptions {
  /** Alignment threshold; align when distance is below this value (default 5) */
  threshold?: number;
  /** Helper line color (default #ff0071) */
  color?: string;
  /** Helper line width (default 1) */
  lineWidth?: number;
  /** Helper line z-index (default 9999) */
  zIndex?: number;
  /** Enable node snapping (default true) */
  enableSnap?: boolean;
} 
