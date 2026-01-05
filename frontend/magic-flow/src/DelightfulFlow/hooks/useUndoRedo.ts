import _ from 'lodash';
import { useMemo, useRef, useState } from 'react';
import {  Edge } from 'reactflow';
import { MagicFlow } from '../types/flow';
import { useMemoizedFn, useUpdateEffect } from 'ahooks';

type Snapshot = {
  nodes: MagicFlow.Node[];
  edges: Edge[];
  nodeConfig: Record<string, any>;
};

const MAX_STACK_SIZE = 40;

const useUndoRedo = (debuggerMode: boolean) => {
  const [undoStack, setUndoStack] = useState<Snapshot[]>([]);
  const [redoStack, setRedoStack] = useState<Snapshot[]>([]);
  const hasUndo = useRef(false)

  useUpdateEffect(() => {
    if(debuggerMode) {
        console.log("Undo stack updated", undoStack)
    }
  }, [undoStack])

   useUpdateEffect(() => {
    if(debuggerMode) {
        console.log("Redo stack updated", redoStack)
    }
  }, [redoStack])

  const takeSnapshot = useMemo(() => 
      (nodes: MagicFlow.Node[], edges: Edge[], nodeConfig: Record<string, any>) => {
        // const snapshot: Snapshot = {
        //   nodes: _.cloneDeep(nodes),
        //   edges: _.cloneDeep(edges),
        //   nodeConfig: _.cloneDeep(nodeConfig),
        // };
        // hasUndo.current = false;
        // setUndoStack((prev) => {
        //   const newStack = [...prev, snapshot];
        //   // Drop oldest snapshot when exceeding max length
        //   if (newStack.length > MAX_STACK_SIZE) {
        //     return newStack.slice(-MAX_STACK_SIZE);
        //   }
        //   return newStack;
        // });
        // setRedoStack([]);
      },
     []
  );
  
  
  /**
   * currentSnapshot：当前快照
   */
  const undo = useMemoizedFn((currentSnapshot: Snapshot) => {
    if (undoStack.length === 0) return; 
    // console.log("undo")

    const lastSnapshot = undoStack[undoStack.length - 1];
    setUndoStack((prev) => prev.slice(0, -1));
    // When redo is empty, also push the current snapshot
    const newRedoStackMembers = redoStack.length === 0 && !hasUndo.current ? [currentSnapshot, lastSnapshot] : [lastSnapshot]
    setRedoStack((prev) => {
      const newStack = [...prev, ...newRedoStackMembers];
      // Drop oldest snapshot when exceeding max length
      if (newStack.length > MAX_STACK_SIZE) {
        return newStack.slice(-MAX_STACK_SIZE);
      }
      return newStack;
    });
    hasUndo.current = true

    return lastSnapshot;
  });

  const redo = useMemoizedFn(() => {
    if (redoStack.length === 0) return;
    // console.log("redo")

    const nextSnapshot = redoStack[redoStack.length - 1];
    setRedoStack((prev) => prev.slice(0, -1));
    setUndoStack((prev) => {
      const newStack = [...prev, nextSnapshot];
      // Drop oldest snapshot when exceeding max length
      if (newStack.length > MAX_STACK_SIZE) {
        return newStack.slice(-MAX_STACK_SIZE);
      }
      return newStack;
    });

    return nextSnapshot;
  });

  return {
    takeSnapshot,
    undo,
    redo,
    canUndo: undoStack.length > 0,
    canRedo: redoStack.length > 0,
  };
};

export default useUndoRedo;