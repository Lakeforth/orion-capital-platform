import { useEffect, useRef, useState } from "react"
import { ChevronDown, Pencil, User } from "lucide-react"
import { AxiosError } from "axios"
import { toast } from "@/components/ui/sonner"
import axiosInstance from "@/lib/axios"
import useUserStore from "@/store/userStore"

const TIMEZONE_OPTIONS = [
  "Eastern Time (US & Canada)",
  "Central Time (US & Canada)",
  "Mountain Time (US & Canada)",
  "Pacific Time (US & Canada)",
  "UTC",
  "London (GMT)",
  "Central European Time",
] as const

type TimezoneOption = (typeof TIMEZONE_OPTIONS)[number]

const TIMEZONE_STORAGE_KEY = "recovery_profile_timezone"

const getFullName = (firstName?: string, lastName?: string) =>
  [firstName, lastName].filter(Boolean).join(" ").trim() || "—"

const splitFullName = (fullName: string, fallbackFirst: string, fallbackLast: string) => {
  const trimmed = fullName.trim()
  if (!trimmed) {
    return { first_name: fallbackFirst, last_name: fallbackLast }
  }

  const parts = trimmed.split(/\s+/)
  if (parts.length === 1) {
    return { first_name: parts[0], last_name: fallbackLast }
  }

  return {
    first_name: parts[0],
    last_name: parts.slice(1).join(" "),
  }
}

const ProfilePersonalDetailsCard = () => {
  const user = useUserStore((state) => state.user)
  const fileInputRef = useRef<HTMLInputElement>(null)

  const [isEditing, setIsEditing] = useState(false)
  const [isSaving, setIsSaving] = useState(false)
  const [fullName, setFullName] = useState("")
  const [email, setEmail] = useState("")
  const [phone, setPhone] = useState("")
  const [timezone, setTimezone] = useState<TimezoneOption>(TIMEZONE_OPTIONS[0])
  const [avatar, setAvatar] = useState<string | null>(null)

  useEffect(() => {
    if (!user) return

    setFullName(getFullName(user.first_name, user.last_name))
    setEmail(user.email ?? "")
    setPhone(user.phone ?? "")
    setAvatar(user.avatar || null)

    const storedTimezone = localStorage.getItem(TIMEZONE_STORAGE_KEY)
    if (storedTimezone && TIMEZONE_OPTIONS.includes(storedTimezone as TimezoneOption)) {
      setTimezone(storedTimezone as TimezoneOption)
    }
  }, [user])

  const memberLabel = user?.account_type?.title
    ? `${user.account_type.title} User`
    : "Recovery Client"

  const handleAvatarChange = (files: FileList | null) => {
    if (!files?.length || !isEditing) return

    const file = files[0]
    if (!file.type.startsWith("image/")) {
      toast.error("Please upload an image file")
      return
    }

    const reader = new FileReader()
    reader.onload = (event) => {
      if (typeof event.target?.result === "string") {
        setAvatar(event.target.result)
      }
    }
    reader.readAsDataURL(file)
  }

  const handleCancelEdit = () => {
    if (!user) return

    setFullName(getFullName(user.first_name, user.last_name))
    setEmail(user.email ?? "")
    setPhone(user.phone ?? "")
    setAvatar(user.avatar || null)
    setIsEditing(false)
  }

  const handleSave = async () => {
    if (!user) return

    if (!fullName.trim()) {
      toast.error("Full legal name is required")
      return
    }

    if (!email.trim()) {
      toast.error("Email address is required")
      return
    }

    const { first_name, last_name } = splitFullName(
      fullName,
      user.first_name,
      user.last_name,
    )

    try {
      setIsSaving(true)

      const response = await axiosInstance.post("/update", {
        first_name,
        last_name,
        email: email.trim(),
        phone: phone.trim(),
        address: user.address ?? "",
        country: user.country ?? "maldives",
        birth_date: user.birth_date ?? "",
        avatar,
      })

      if (response.data) {
        localStorage.setItem(TIMEZONE_STORAGE_KEY, timezone)
        useUserStore.getState().setUser(
          {
            ...user,
            first_name,
            last_name,
            email: email.trim(),
            phone: phone.trim(),
            avatar: avatar || user.avatar,
          },
          useUserStore.getState().token!,
        )
        toast.success("Profile updated successfully")
        setIsEditing(false)
      }
    } catch (error) {
      const errorMessage =
        (error as AxiosError<{ message: string }>).response?.data?.message ||
        "Failed to update profile. Please try again."
      toast.error(errorMessage)
    } finally {
      setIsSaving(false)
    }
  }

  const inputClassName = `w-full px-4 py-2.5 rounded-lg text-[13px] text-[#0A1628] border transition-colors ${
    isEditing
      ? "bg-white border-[#E2E8F0] focus:outline-none focus:ring-2 focus:ring-[#2563EB]/20 focus:border-[#2563EB]"
      : "bg-[#F8FAFC] border-[#EEF1F5] cursor-default"
  }`

  return (
    <section className="bg-white rounded-xl border border-[#EEF1F5] shadow-[0_1px_4px_rgba(0,0,0,0.06)]">
      <div className="flex items-center justify-between gap-4 px-6 py-5 border-b border-[#EEF1F5]">
        <h2 className="text-[#0A1628] text-[16px] font-bold">Personal Details</h2>
        {isEditing ? (
          <button
            type="button"
            onClick={handleCancelEdit}
            className="px-4 py-2 text-[#6B7A8D] text-[13px] font-semibold rounded-lg border border-[#E2E8F0] hover:bg-[#F8FAFC]"
          >
            Cancel
          </button>
        ) : (
          <button
            type="button"
            onClick={() => setIsEditing(true)}
            className="px-4 py-2 text-[#2563EB] text-[13px] font-semibold rounded-lg border border-[#BFDBFE] hover:bg-[#F0F7FF]"
          >
            Edit Profile
          </button>
        )}
      </div>

      <div className="px-6 py-6">
        <div className="flex items-start gap-5 mb-8">
          <div className="relative shrink-0">
            <div className="w-[88px] h-[88px] rounded-xl overflow-hidden bg-[#EEF1F5] flex items-center justify-center">
              {avatar ? (
                <img
                  src={avatar}
                  alt={fullName}
                  className="w-full h-full object-cover"
                />
              ) : (
                <User className="w-10 h-10 text-[#94A3B8]" />
              )}
            </div>
            {isEditing && (
              <>
                <input
                  ref={fileInputRef}
                  type="file"
                  accept="image/*"
                  className="hidden"
                  onChange={(event) => handleAvatarChange(event.target.files)}
                  aria-label="Upload profile photo"
                />
                <button
                  type="button"
                  onClick={() => fileInputRef.current?.click()}
                  className="absolute -bottom-1 -right-1 w-8 h-8 rounded-full bg-[#2563EB] text-white flex items-center justify-center shadow-md hover:bg-[#1D4ED8]"
                  aria-label="Change profile photo"
                >
                  <Pencil className="w-3.5 h-3.5" />
                </button>
              </>
            )}
          </div>

          <div className="pt-1">
            <h3 className="text-[#0A1628] text-[18px] font-bold mb-1">
              {fullName}
            </h3>
            <p className="text-[#94A3B8] text-[13px]">
              {memberLabel}
            </p>
          </div>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
          <div>
            <label
              htmlFor="profile-full-name"
              className="block text-[#6B7A8D] text-[12px] font-medium mb-2"
            >
              Full Legal Name
            </label>
            <input
              id="profile-full-name"
              type="text"
              value={fullName}
              onChange={(event) => setFullName(event.target.value)}
              readOnly={!isEditing}
              className={inputClassName}
            />
          </div>

          <div>
            <label
              htmlFor="profile-email"
              className="block text-[#6B7A8D] text-[12px] font-medium mb-2"
            >
              Email Address
            </label>
            <input
              id="profile-email"
              type="email"
              value={email}
              onChange={(event) => setEmail(event.target.value)}
              readOnly={!isEditing}
              className={inputClassName}
            />
          </div>

          <div>
            <label
              htmlFor="profile-phone"
              className="block text-[#6B7A8D] text-[12px] font-medium mb-2"
            >
              Phone Number
            </label>
            <input
              id="profile-phone"
              type="tel"
              value={phone}
              onChange={(event) => setPhone(event.target.value)}
              readOnly={!isEditing}
              className={inputClassName}
            />
          </div>

          <div>
            <label
              htmlFor="profile-timezone"
              className="block text-[#6B7A8D] text-[12px] font-medium mb-2"
            >
              Time Zone
            </label>
            <div className="relative">
              <select
                id="profile-timezone"
                value={timezone}
                onChange={(event) => setTimezone(event.target.value as TimezoneOption)}
                disabled={!isEditing}
                className={`${inputClassName} appearance-none pr-10 ${
                  !isEditing ? "opacity-100" : ""
                }`}
              >
                {TIMEZONE_OPTIONS.map((option) => (
                  <option key={option} value={option}>
                    {option}
                  </option>
                ))}
              </select>
              <ChevronDown className="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[#94A3B8]" />
            </div>
          </div>
        </div>
      </div>

      {isEditing && (
        <div className="px-6 py-5 border-t border-[#EEF1F5] flex justify-end">
          <button
            type="button"
            onClick={handleSave}
            disabled={isSaving}
            className="px-6 py-2.5 bg-[#2563EB] hover:bg-[#1D4ED8] disabled:opacity-60 text-white text-[13px] font-semibold rounded-lg transition-colors"
          >
            {isSaving ? "Saving..." : "Save Changes"}
          </button>
        </div>
      )}
    </section>
  )
}

export default ProfilePersonalDetailsCard
