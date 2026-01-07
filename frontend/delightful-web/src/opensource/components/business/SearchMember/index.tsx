import DelightfulSearch from "@/opensource/components/base/DelightfulSearch"
import DelightfulInfiniteScrollList from "@/opensource/components/DelightfulInfiniteScrollList"
import type { StructureUserItem } from "@/types/organization"
import { StructureItemType } from "@/types/organization"
import type { PaginationResponse } from "@/types/request"
import { useControllableValue, useDebounce, useMemoizedFn } from "ahooks"
import { useCallback, memo, useEffect, useState } from "react"
import { useTranslation } from "react-i18next"
import useSWRMutation from "swr/mutation"
import type { DelightfulListItemData } from "@/opensource/components/DelightfulList/types"
import DelightfulScrollBar from "@/opensource/components/base/DelightfulScrollBar"
import { Flex, Spin } from "antd"
import type { CSSProperties } from "react"
import { ContactApi } from "@/apis"
import type { UserSelectItem } from "../MemberDepartmentSelectPanel/types"
import type { CheckboxOptions } from "../OrganizationPanel/types"

export interface MemberSearchProps {
	onSelect?: (member: UserSelectItem) => void
	checkboxOptions?: CheckboxOptions
	noSearchFallback?: React.ReactNode
	onChangeSearchValue?: (value: string) => void
	searchValue?: string
	listClassName?: string
	className?: string
	onFocusChange?: (isFocused: boolean) => void
	showSearchResults?: boolean
	style?: CSSProperties
	containerHeight?: number
	filterResult?: (result: any) => any
}

type Data = UserSelectItem & DelightfulListItemData

// Define Props for the search result list component
interface SearchResultListProps {
	debounceSearchValue: string
	listClassName?: string
	data?: PaginationResponse<StructureUserItem>
	trigger: (args: { page_token?: string }) => Promise<PaginationResponse<StructureUserItem>>
	onItemClick?: (item: UserSelectItem) => void
	checkboxOptions?: CheckboxOptions
	isSearching: boolean
	showSearchResults?: boolean
	containerHeight?: number
}

// Transform search result items to list items
const transformUserToListItem = (user: StructureUserItem): Data => {
	return {
		...user,
		dataType: StructureItemType.User,
		id: user.user_id,
		title: user.real_name,
		avatar: {
			src: user.avatar_url,
			children: user.real_name,
		},
	}
}

// Optimization: Split search result list into a separate component
const SearchResultList = memo(function SearchResultList({
	debounceSearchValue,
	listClassName,
	data,
	trigger,
	onItemClick,
	checkboxOptions,
	isSearching,
	showSearchResults,
	containerHeight,
}: SearchResultListProps) {
	// If there's no search term, don't display search results
	if (!debounceSearchValue) return null

	// Only return null when search is complete and there are no results
	// Still render the component during initial search or loading to trigger the search
	const hasNoResults = data && data.items && data.items.length === 0

	// Adjust container height to ensure it fills remaining space when there are search results
	const containerStyle = {
		height: showSearchResults ? "calc(100% - 38px)" : "0", // 38px is the height of the search box
		opacity: showSearchResults ? 1 : 0,
		overflow: "hidden",
		transition: "height 0.3s ease, opacity 0.3s ease",
		flex: showSearchResults ? 1 : "none", // Allow container to fill space when displayed
		marginBottom: 0,
		paddingBottom: 0,
		display: "flex", // Add flex layout
		flexDirection: "column" as const, // Use column direction arrangement
	}

	// Display loading state or search results
	return (
		<div style={containerStyle}>
			<DelightfulScrollBar
				className={listClassName}
				style={{
					height: "100%",
					overflowY: "auto",
					flex: 1, // Let scroll container fill the space
					display: "flex",
					flexDirection: "column",
				}}
			>
				{isSearching && !data ? (
					<Flex
						justify="center"
						align="center"
						style={{ height: "100px", width: "100%" }}
					>
						<Spin />
					</Flex>
				) : (
					<DelightfulInfiniteScrollList
						data={data}
						trigger={trigger}
						itemsTransform={transformUserToListItem}
						onItemClick={onItemClick}
						// @ts-ignore - The generic type expected by DelightfulInfiniteScrollList doesn't match what we provide
						// but it will work fine at runtime because all required properties exist
						checkboxOptions={
							checkboxOptions
								? {
										checked: checkboxOptions.checked,
										onChange: checkboxOptions.onChange,
										disabled: checkboxOptions.disabled,
										dataType: StructureItemType.User,
								  }
								: undefined
						}
						noDataFallback={hasNoResults ? null : undefined}
						// Don't set fixed height, let list height adapt to container
						style={{ flex: 1 }} // Let list fill container space
						containerHeight={containerHeight}
					/>
				)}
			</DelightfulScrollBar>
		</div>
	)
})

const MemberSearch = (props: MemberSearchProps) => {
	const { t } = useTranslation("interface")

	const {
		onSelect,
		checkboxOptions,
		listClassName,
		className,
		onFocusChange,
		showSearchResults,
		style,
		containerHeight,
	} = props

	const [searchValue, setSearchValue] = useControllableValue(props, {
		defaultValue: "",
		trigger: "onChangeSearchValue",
		valuePropName: "searchValue",
	})

	// Add focus state management
	const [isFocused, setIsFocused] = useState(false)

	// Notify parent component when focus state changes
	useEffect(() => {
		onFocusChange?.(isFocused)
	}, [isFocused, onFocusChange])

	// Optimization: Increase debounce time to 800ms to reduce search request frequency
	const debounceSearchValue = useDebounce(searchValue, {
		wait: 800,
	})

	// Add search state management
	const [isSearching, setIsSearching] = useState(false)
	// Store current search results
	const [searchResults, setSearchResults] = useState<
		PaginationResponse<StructureUserItem> | undefined
	>()

	// Use useSWRMutation but don't directly use its returned data
	const { trigger: searchUser } = useSWRMutation<
		PaginationResponse<StructureUserItem>,
		any,
		string | false,
		{ page_token?: string; query: string; query_type: 1 | 2 }
	>(debounceSearchValue ? `searchUser/${debounceSearchValue}` : false, (_, { arg }) => {
		return ContactApi.searchUser(arg)
	})

	// Reset search results and trigger new search when search value changes
	useEffect(() => {
		if (debounceSearchValue) {
			// Set search state to searching
			setIsSearching(true)
			// Clear current results to avoid displaying previous search results
			setSearchResults(undefined)

			searchUser({
				query: debounceSearchValue,
				query_type: 1,
				page_token: "",
			})
				.then((result) => {
					if (props.filterResult) {
						result.items = props.filterResult(result.items)
					}
					setSearchResults(result)
					setIsSearching(false)
				})
				.catch(() => {
					setIsSearching(false)
				})
		} else {
			// Clear results when there's no search term
			setSearchResults(undefined)
			setIsSearching(false)
		}
	}, [debounceSearchValue, searchUser])

	const trigger = useCallback(
		({ page_token = "" }: { page_token?: string }) => {
			if (!debounceSearchValue)
				return Promise.resolve({ items: [], has_more: false, page_token: "" })

			// If requesting more pages, don't reset current results
			if (page_token) {
				return searchUser({
					query: debounceSearchValue,
					query_type: 1,
					page_token,
				}).then((result) => {
					// Update search results
					setSearchResults((prev) => {
						if (!prev) return result
						return {
							...result,
							items: [...prev.items, ...result.items],
						}
					})
					return result
				})
			}

			// First page request logic
			setIsSearching(true)
			return searchUser({
				query: debounceSearchValue,
				query_type: 1,
				page_token,
			})
				.then((result) => {
					setSearchResults(result)
					setIsSearching(false)
					return result
				})
				.catch((err) => {
					setIsSearching(false)
					throw err
				})
		},
		[debounceSearchValue, searchUser],
	)

	const handleSearchChange = useMemoizedFn((e: React.ChangeEvent<HTMLInputElement>) => {
		setSearchValue(e.target.value)
	})

	// Handle search box focus event
	const handleFocus = useMemoizedFn(() => {
		setIsFocused(true)
	})

	// Handle search box blur event
	const handleBlur = useMemoizedFn(() => {
		setIsFocused(false)
	})

	// Handle list item click
	const handleItemClick = useMemoizedFn((item: UserSelectItem) => {
		onSelect?.(item)
	})

	return (
		<Flex vertical gap={10} className={className} style={style}>
			<DelightfulSearch
				value={searchValue}
				onChange={handleSearchChange}
				onFocus={handleFocus}
				onBlur={handleBlur}
				allowClear
				placeholder={t("memberSearch.searchMembers")}
			/>
			<SearchResultList
				debounceSearchValue={debounceSearchValue}
				listClassName={listClassName}
				data={searchResults}
				trigger={trigger}
				onItemClick={handleItemClick}
				checkboxOptions={checkboxOptions}
				isSearching={isSearching}
				showSearchResults={showSearchResults}
				containerHeight={containerHeight}
			/>
		</Flex>
	)
}

const MemoizedMemberSearch = memo(MemberSearch)
export default MemoizedMemberSearch
