import { MaterialGroup } from "@/DelightfulFlow/context/MaterialSourceContext/MaterialSourceContext"
import { BaseNodeType, NodeGroup, NodeWidget } from "@/DelightfulFlow/register/node"
import React, { ReactNode, useState, useRef, useEffect, memo } from "react"
import SubGroup from "../SubGroup/SubGroup"

interface LazySubGroupProps {
    subGroup: NodeGroup | MaterialGroup
    getGroupNodeList: (nodeTypes: BaseNodeType[]) => NodeWidget[]
    materialFn: (n: NodeWidget, extraProps: Record<string, any>) => ReactNode
    index: number
}

/**
 * Lazy load SubGroup component, only render inside when component enters viewport
 */
function LazySubGroup({ subGroup, getGroupNodeList, materialFn, index }: LazySubGroupProps) {
    const [isVisible, setIsVisible] = useState(false);
    const [isLoaded, setIsLoaded] = useState(false);
    const ref = useRef<HTMLDivElement>(null);

    useEffect(() => {
        const observer = new IntersectionObserver(
            ([entry]) => {
                //  When component enters viewport
                if (entry.isIntersecting) {
                    setIsVisible(true);
                    
                    //  Mark as already loaded, avoid re-rendering
                    if (!isLoaded) {
                        setIsLoaded(true);
                    }
                    
                    //  component already loaded, can unobserve
                    if (ref.current) {
                        observer.unobserve(ref.current);
                    }
                }
            },
            {
                rootMargin: '100px', //  start loading 100px ahead
                threshold: 0.1 //  10% visible threshold
            }
        );

        if (ref.current) {
            observer.observe(ref.current);
        }

        return () => {
            if (ref.current) {
                observer.unobserve(ref.current);
            }
        };
    }, [isLoaded]);

    //  Render item placeholder or actual content
    return (
        <div ref={ref} style={{ minHeight: '50px' }}>
            {(isVisible || isLoaded) ? (
                <SubGroup
                    subGroup={subGroup}
                    getGroupNodeList={getGroupNodeList}
                    materialFn={(n, extraProps) => materialFn(n, {
                        ...extraProps,
                        key: `item-${index}-${n.schema?.id || 0}`
                    })}
                />
            ) : (
                <div style={{ height: '50px', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                    loadin...
                </div>
            )}
        </div>
    );
}

export default memo(LazySubGroup); 
