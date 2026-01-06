import { useState, useCallback } from "react";
import { Node } from "reactflow";
import { HelperLinesOptions } from "../components/HelperLines/types";

interface UseHelperLinesProps {
  /** Flowchart nodes */
  nodes: Node[];
  /** Original node drag handler */
  onNodeDrag?: (event: React.MouseEvent, node: Node) => void;
  /** Original node drag-start handler */
  onNodeDragStart?: (event: React.MouseEvent, node: Node) => void;
  /** Original node drag-stop handler */
  onNodeDragStop?: (event: React.MouseEvent, node: Node, nodes?: Node[]) => void;
  /** Node change handler to support snapping */
  onNodesChange?: (changes: any[]) => void;
  /** Helper line options */
  options?: HelperLinesOptions;
  /** Whether helper lines are enabled */
  enabled?: boolean;
}

/**
 * Helper-lines hook: show alignment guides and snap nodes during drag
 * 
 * @param props Options
 * @returns Helper line state and handlers
 */
export function useHelperLines(props: UseHelperLinesProps) {
  const { 
    nodes, 
    onNodeDrag, 
    onNodeDragStart, 
    onNodeDragStop,
    onNodesChange,
    options = {},
    enabled = false
  } = props;
  
  // Snap threshold; default to 5
  const SNAP_THRESHOLD = options.threshold || 5;
  
  // Whether snapping is enabled
  const enableSnap = options.enableSnap !== false;

  // Helper line state
  const [horizontalLines, setHorizontalLines] = useState<number[]>([]);
  const [verticalLines, setVerticalLines] = useState<number[]>([]);

  // Node currently being dragged
  const [draggedNode, setDraggedNode] = useState<Node | null>(null);

  // Handle node drag start
  const handleNodeDragStart = useCallback(
    (event: React.MouseEvent, node: Node) => {
      if (enabled) {
        setDraggedNode(node);
      }
      
      if (onNodeDragStart) {
        onNodeDragStart(event, node);
      }
    },
    [onNodeDragStart, enabled]
  );

  // Handle node drag
  const handleNodeDrag = useCallback(
    (event: React.MouseEvent, node: Node) => {
      // If helper lines are disabled, call the original handler and exit
      if (!enabled) {
        if (onNodeDrag) {
          onNodeDrag(event, node);
        }
        return;
      }

      if (!draggedNode) return;

      // Clear previous helper lines
      setHorizontalLines([]);
      setVerticalLines([]);

      // Ignore the node currently being dragged
      const otherNodes = nodes.filter((n) => n.id !== node.id);

      const horizontalAlignments: number[] = [];
      const verticalAlignments: number[] = [];
      
      // Coordinates to snap to
      let snapX: number | null = null;
      let snapY: number | null = null;

      // Compare the dragged node position against others
      otherNodes.forEach((otherNode) => {
        // Horizontal alignment — top
        if (Math.abs(node.position.y - otherNode.position.y) < SNAP_THRESHOLD) {
          horizontalAlignments.push(otherNode.position.y);
          if (enableSnap) {
            snapY = otherNode.position.y;
          }
        }

        // Horizontal alignment — bottom (assumes nodes have height)
        const nodeHeight = (node as any).height || 40;
        const otherNodeHeight = (otherNode as any).height || 40;
        if (
          Math.abs(
            node.position.y + nodeHeight - (otherNode.position.y + otherNodeHeight)
          ) < SNAP_THRESHOLD
        ) {
          horizontalAlignments.push(otherNode.position.y + otherNodeHeight);
          if (enableSnap) {
            snapY = otherNode.position.y + otherNodeHeight - nodeHeight;
          }
        }

        // Horizontal alignment — center
        if (
          Math.abs(
            node.position.y + nodeHeight / 2 - (otherNode.position.y + otherNodeHeight / 2)
          ) < SNAP_THRESHOLD
        ) {
          horizontalAlignments.push(otherNode.position.y + otherNodeHeight / 2);
          if (enableSnap) {
            snapY = otherNode.position.y + (otherNodeHeight / 2) - (nodeHeight / 2);
          }
        }

        // Vertical alignment — left
        if (Math.abs(node.position.x - otherNode.position.x) < SNAP_THRESHOLD) {
          verticalAlignments.push(otherNode.position.x);
          if (enableSnap) {
            snapX = otherNode.position.x;
          }
        }

        // Vertical alignment — right
        const nodeWidth = (node as any).width || 150;
        const otherNodeWidth = (otherNode as any).width || 150;
        if (
          Math.abs(
            node.position.x + nodeWidth - (otherNode.position.x + otherNodeWidth)
          ) < SNAP_THRESHOLD
        ) {
          verticalAlignments.push(otherNode.position.x + otherNodeWidth);
          if (enableSnap) {
            snapX = otherNode.position.x + otherNodeWidth - nodeWidth;
          }
        }

        // Vertical alignment — center
        if (
          Math.abs(
            node.position.x + nodeWidth / 2 - (otherNode.position.x + otherNodeWidth / 2)
          ) < SNAP_THRESHOLD
        ) {
          verticalAlignments.push(otherNode.position.x + otherNodeWidth / 2);
          if (enableSnap) {
            snapX = otherNode.position.x + (otherNodeWidth / 2) - (nodeWidth / 2);
          }
        }
      });

      // Update helper lines
      setHorizontalLines(horizontalAlignments);
      setVerticalLines(verticalAlignments);

      // Apply snap positions
      if (enableSnap && onNodesChange && (snapX !== null || snapY !== null)) {
        // New node position with snap applied
        const newPosition = {
          x: snapX !== null ? snapX : node.position.x,
          y: snapY !== null ? snapY : node.position.y,
        };
        
        // Update node position to apply snapping
        const nodeChange = {
          id: node.id,
          type: 'position',
          position: newPosition,
        };
        
        onNodesChange([nodeChange]);
      } else {
        // If not snapping, still call the original handler
        if (onNodeDrag) {
          onNodeDrag(event, node);
        }
      }
    },
    [draggedNode, nodes, onNodeDrag, onNodesChange, SNAP_THRESHOLD, enableSnap, enabled]
  );

  // Handle node drag end
  const handleNodeDragStop = useCallback(
    (event: React.MouseEvent, node: Node, dragNodes?: Node[]) => {
      // Clear helper lines and drag state
      if (enabled) {
        setHorizontalLines([]);
        setVerticalLines([]);
        setDraggedNode(null);
      }

      // Invoke the original drag-stop handler
      if (onNodeDragStop) {
        onNodeDragStop(event, node, dragNodes);
      }
    },
    [onNodeDragStop, enabled]
  );

  return {
    // State
    horizontalLines,
    verticalLines,
    
    // Helper line options
    options,
    
    // Whether enabled
    enabled,
    
    // Event handlers
    handleNodeDragStart,
    handleNodeDrag,
    handleNodeDragStop,
    
    // Derived: helper lines present
    hasHelperLines: enabled && (horizontalLines.length > 0 || verticalLines.length > 0)
  };
} 
