import { Button } from '@/components/ui/button';
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from '@/components/ui/command';
import { Input } from '@/components/ui/input';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { cn } from '@/lib/utils';
import { Check, ChevronsUpDown } from 'lucide-react';
import { useEffect, useState } from 'react';

import { PublicPost_T } from '@/types/public-post-types';
import CreatePublicPostModal from './create-public-post-modal';

interface PublicPostTabProps {
    posts: PublicPost_T[];
    setFilteredPosts: (posts: PublicPost_T[]) => void;
}

const PublicPostTab = ({ posts, setFilteredPosts }: PublicPostTabProps) => {
    const [categoryOpen, setCategoryOpen] = useState(false);
    const [statusOpen, setStatusOpen] = useState(false);
    const [categoryValue, setCategoryValue] = useState<string | null>(null);
    const [statusValue, setStatusValue] = useState<string | null>(null);
    const [searchQuery, setSearchQuery] = useState<string>('');
    const [searchableCategories, setSearchableCategories] = useState<string[]>(
        [],
    );

    // Extract unique categories from posts data
    useEffect(() => {
        const categories = posts
            .map((post: PublicPost_T) => post.category)
            .filter((category): category is string => Boolean(category))
            .filter(
                (value: string, index: number, self: string[]) =>
                    self.indexOf(value) === index,
            );
        setSearchableCategories(categories);
    }, [posts]);

    // Filter displayed posts based on selected category, status, and search query
    useEffect(() => {
        let filteredResults = posts;

        // Filter by category if selected
        if (categoryValue) {
            filteredResults = filteredResults.filter(
                (post: PublicPost_T) => post.category === categoryValue,
            );
        }

        // Filter by status if selected
        if (statusValue) {
            const now = new Date();
            filteredResults = filteredResults.filter((post: PublicPost_T) => {
                switch (statusValue) {
                    case 'published':
                        return (
                            post.published_at &&
                            new Date(post.published_at) <= now
                        );
                    case 'draft':
                        return !post.published_at;
                    case 'scheduled':
                        return (
                            post.published_at &&
                            new Date(post.published_at) > now
                        );
                    default:
                        return true;
                }
            });
        }

        // Filter by search query (title or content)
        if (searchQuery.trim()) {
            filteredResults = filteredResults.filter((post: PublicPost_T) => {
                const title = post.title?.toLowerCase() || '';
                const content = post.content?.toLowerCase() || '';
                const publisherName =
                    post.publishedBy?.name?.toLowerCase() || '';
                const query = searchQuery.toLowerCase();

                return (
                    title.includes(query) ||
                    content.includes(query) ||
                    publisherName.includes(query)
                );
            });
        }

        setFilteredPosts(filteredResults);
    }, [categoryValue, statusValue, searchQuery, posts, setFilteredPosts]);

    const statusOptions = [
        { value: 'published', label: 'Published' },
        { value: 'draft', label: 'Draft' },
        { value: 'scheduled', label: 'Scheduled' },
    ];

    return (
        <div className="flex w-full flex-wrap gap-4">
            <CreatePublicPostModal />

            <Input
                placeholder="Search by title, content, or publisher"
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                className="h-12 min-w-[300px] flex-1"
            />

            <Popover open={categoryOpen} onOpenChange={setCategoryOpen}>
                <PopoverTrigger asChild className="h-12">
                    <Button
                        variant="outline"
                        role="combobox"
                        aria-expanded={categoryOpen}
                        className="w-[180px] cursor-pointer justify-between"
                    >
                        {categoryValue || 'Select category...'}
                        <ChevronsUpDown className="opacity-50" />
                    </Button>
                </PopoverTrigger>
                <PopoverContent className="w-[180px] p-0">
                    <Command>
                        <CommandInput
                            placeholder="Search category..."
                            className="h-9"
                        />
                        <CommandList>
                            <CommandEmpty>No category found.</CommandEmpty>
                            <CommandGroup>
                                <CommandItem
                                    key="all-categories"
                                    value=""
                                    onSelect={() => {
                                        setCategoryValue(null);
                                        setCategoryOpen(false);
                                    }}
                                >
                                    All Categories
                                    <Check
                                        className={cn(
                                            'ml-auto',
                                            categoryValue === null
                                                ? 'opacity-100'
                                                : 'opacity-0',
                                        )}
                                    />
                                </CommandItem>
                                {searchableCategories.map((category) => (
                                    <CommandItem
                                        key={category}
                                        value={category}
                                        onSelect={(currentValue) => {
                                            setCategoryValue(
                                                currentValue === categoryValue
                                                    ? null
                                                    : currentValue,
                                            );
                                            setCategoryOpen(false);
                                        }}
                                    >
                                        {category}
                                        <Check
                                            className={cn(
                                                'ml-auto',
                                                categoryValue === category
                                                    ? 'opacity-100'
                                                    : 'opacity-0',
                                            )}
                                        />
                                    </CommandItem>
                                ))}
                            </CommandGroup>
                        </CommandList>
                    </Command>
                </PopoverContent>
            </Popover>

            <Popover open={statusOpen} onOpenChange={setStatusOpen}>
                <PopoverTrigger asChild className="h-12">
                    <Button
                        variant="outline"
                        role="combobox"
                        aria-expanded={statusOpen}
                        className="w-[150px] cursor-pointer justify-between"
                    >
                        {statusValue
                            ? statusOptions.find((s) => s.value === statusValue)
                                  ?.label
                            : 'Select status...'}
                        <ChevronsUpDown className="opacity-50" />
                    </Button>
                </PopoverTrigger>
                <PopoverContent className="w-[150px] p-0">
                    <Command>
                        <CommandInput
                            placeholder="Search status..."
                            className="h-9"
                        />
                        <CommandList>
                            <CommandEmpty>No status found.</CommandEmpty>
                            <CommandGroup>
                                <CommandItem
                                    key="all-status"
                                    value=""
                                    onSelect={() => {
                                        setStatusValue(null);
                                        setStatusOpen(false);
                                    }}
                                >
                                    All Status
                                    <Check
                                        className={cn(
                                            'ml-auto',
                                            statusValue === null
                                                ? 'opacity-100'
                                                : 'opacity-0',
                                        )}
                                    />
                                </CommandItem>
                                {statusOptions.map((status) => (
                                    <CommandItem
                                        key={status.value}
                                        value={status.value}
                                        onSelect={(currentValue) => {
                                            setStatusValue(
                                                currentValue === statusValue
                                                    ? null
                                                    : currentValue,
                                            );
                                            setStatusOpen(false);
                                        }}
                                    >
                                        {status.label}
                                        <Check
                                            className={cn(
                                                'ml-auto',
                                                statusValue === status.value
                                                    ? 'opacity-100'
                                                    : 'opacity-0',
                                            )}
                                        />
                                    </CommandItem>
                                ))}
                            </CommandGroup>
                        </CommandList>
                    </Command>
                </PopoverContent>
            </Popover>
        </div>
    );
};

export default PublicPostTab;
