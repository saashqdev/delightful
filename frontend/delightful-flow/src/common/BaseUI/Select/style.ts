import styled, {createGlobalStyle} from "styled-components"

export const GlobalStyle = createGlobalStyle`
    
    .delightful-select-item-option-selected:not(.delightful-select-item-option-disabled) {
        background-color: #fff;
        color: #3B81F7;
    }
    .delightful-select-item-option-active:not(.delightful-select-item-option-disabled) {
        background-color: #F5F9FF;
        color: #3B81F7;
    }
    .delightful-select-item-option-selected:not(.delightful-select-item-option-disabled) .delightful-select-item-option-content,
    .delightful-select-item-option-content {
        padding-left: 0;
    }
    .delightful-select-item-option-state {
        display: none;
    }
    .delightful-select-item-option-selected:not(.delightful-select-item-option-disabled) {
        background-color: #fff;
    }

    .delightful-select, ant-mobile-select {
        min-height: 32px;
		color: #333333;
		&:hover {
            .delightful-select-selector {
                border-color: #3B81F7!important;
            }
		}

        
        .delightful-select-selector {
            position: relative!important;
            background-color: #fff!important;
            border: 1px solid #1C1D2314!important;
            border-radius: 8px!important;
            min-height: 33px!important;
        }
        .delightful-select-selection-item {
            padding-right: 14px;
            display: flex;
            align-items: center;
            padding-right: 0!important;
            margin-right: 18px;
            text-overflow: ellipsis;
        }
        .delightful-select-arrow {
            color: #1C1D2359;
            svg {
                width: 16px;
                height: 16px;
            }
        }

    }

    .delightful-select-disabled {
        
        .delightful-select-selector {
            background: #EEEEEE;  
            color: #999999;
            border-color: #d9d9d9;
        }
        .delightful-select-arrow {  
            .default-suffix-icon {
                color: #999999;
            }
        }
    }

    &.delightful-select-multiple .delightful-select-selector {
		padding: 1px 4px;

    }

`

export const SelectWrapper = styled.div`
`


