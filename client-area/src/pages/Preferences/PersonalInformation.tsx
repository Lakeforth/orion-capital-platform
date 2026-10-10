import { PhotoGuidelines } from "@/components/personal-info/photo-guidelines";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Card } from "@/components/ui/card";
import { User } from "lucide-react";
import { useForm } from "react-hook-form";
import { z } from "zod";
import { zodResolver } from "@hookform/resolvers/zod";
import * as React from "react";
import axiosInstance from "@/lib/axios";
import { toast } from "sonner";
import useUserStore from "@/store/userStore";
import { countries } from "@/data/countries";
import { useEffect } from "react";
import { AxiosError } from "axios";

const formSchema = z.object({
  first_name: z.string().min(2, "First name must be at least 2 characters"),
  last_name: z.string().min(2, "Last name must be at least 2 characters"),
  address: z.string().min(5, "Address must be at least 5 characters"),
  city: z.string().optional(),
  zip_code: z.string().optional(),
  country: z.string().min(1, "Please select a country"),
  birth_date: z.string().min(1, "Date of birth is required"),
  email: z.string().email("Please enter a valid email address"),
  phone: z.string().min(5, "Phone number must be at least 5 characters"),
  avatar: z.string().nullable(),
});

type FormValues = z.infer<typeof formSchema>;

const countryOptionValue = (stored: string) => {
  const wanted = stored.toLowerCase();
  return (
    countries.find((c) => c.value === wanted || c.label.toLowerCase() === wanted)?.value ?? stored
  );
};

export default function PersonalInformation() {
  const [image, setImage] = React.useState<string | null>(null);
  const [isDragging, setIsDragging] = React.useState(false);
  const [isLoading, setIsLoading] = React.useState(false);
  const user = useUserStore((state) => state.user);
  const countryValue = countryOptionValue(user?.country || "maldives");

  const {
    register,
    handleSubmit,
    setValue,
    formState: { errors },
  } = useForm<FormValues>({
    resolver: zodResolver(formSchema),
    defaultValues: {
      first_name: user?.first_name || "",
      last_name: user?.last_name || "",
      address: user?.address || "",
      city: user?.city || "",
      zip_code: user?.zip_code || "",
      country: user?.country || "maldives",
      birth_date: user?.birth_date || "",
      email: user?.email || "",
      phone: user?.phone || "",
      avatar: user?.avatar || null,
    },
  });

  useEffect(() => {
    if (user) {
      setImage(user?.avatar || null);
    }
  }, []);

  const onSubmit = async (data: FormValues) => {
    try {
      setIsLoading(true);
      const response = await axiosInstance.post("/update", {
        ...data,
        avatar: image,
      });

      if (response.data) {
        toast.success("Profile updated successfully");
        // Update the user store with new data if needed
        if (user) {
          useUserStore.getState().setUser(
            {
              ...user,
              first_name: data.first_name,
              last_name: data.last_name,
              country: data.country,
              city: data.city,
              zip_code: data.zip_code,
              birth_date: data.birth_date,
              email: data.email,
              address: data.address,
              phone: data.phone,
              avatar: image || user.avatar,
            },
            useUserStore.getState().token!
          );
        }
      }
    } catch (error) {
      console.error("Failed to update profile:", error);
      const errorMessage =
        (error as AxiosError<{ message: string }>).response?.data?.message ||
        "Failed to update profile. Please try again.";
      toast.error(errorMessage);
    } finally {
      setIsLoading(false);
    }
  };

  const handleDragOver = (e: React.DragEvent) => {
    e.preventDefault();
    setIsDragging(true);
  };

  const handleDragLeave = () => {
    setIsDragging(false);
  };

  const handleDrop = (e: React.DragEvent) => {
    e.preventDefault();
    setIsDragging(false);
    handleImageUpload(e.dataTransfer.files);
  };

  const handleImageUpload = (files: FileList | null) => {
    if (files && files.length > 0) {
      const file = files[0];
      if (file.type.startsWith("image/")) {
        const reader = new FileReader();
        reader.onload = (event) => {
          if (event.target?.result) {
            const imageData = event.target.result as string;
            setImage(imageData);
            setValue("avatar", imageData);
          }
        };
        reader.readAsDataURL(file);
      }
    }
  };

  const handleClick = () => {
    const input = document.createElement("input");
    input.type = "file";
    input.accept = "image/*";
    input.onchange = (e) => {
      handleImageUpload((e.target as HTMLInputElement).files);
    };
    input.click();
  };

  return (
    <form
      onSubmit={handleSubmit(onSubmit)}
      className="flex flex-col gap-8 p-6 bg-background text-foreground min-h-screen"
    >
      <h1 className="text-2xl font-bold text-center">PERSONAL INFORMATION</h1>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div className="space-y-2">
          <label htmlFor="first_name" className="text-muted-foreground">
            First Name
          </label>
          <Input
            id="first_name"
            {...register("first_name")}
            className="bg-card-foreground border-card-foreground/10"
          />
          {errors.first_name && (
            <p className="text-red-500 text-sm">{errors.first_name.message}</p>
          )}
        </div>

        <div className="space-y-2">
          <label htmlFor="last_name" className="text-muted-foreground">
            Last Name
          </label>
          <Input
            id="last_name"
            {...register("last_name")}
            className="bg-card border-card-foreground/10"
          />
          {errors.last_name && (
            <p className="text-red-500 text-sm">{errors.last_name.message}</p>
          )}
        </div>

        <div className="space-y-2">
          <label htmlFor="address" className="text-muted-foreground">
            Address
          </label>
          <Input
            id="address"
            {...register("address")}
            className="bg-card border-card-foreground/10"
          />
          {errors.address && (
            <p className="text-red-500 text-sm">{errors.address.message}</p>
          )}
        </div>

        <div className="space-y-2">
          <label htmlFor="city" className="text-muted-foreground">
            City
          </label>
          <Input
            id="city"
            {...register("city")}
            className="bg-card border-card-foreground/10"
          />
          {errors.city && (
            <p className="text-red-500 text-sm">{errors.city.message}</p>
          )}
        </div>

        <div className="space-y-2">
          <label htmlFor="zip_code" className="text-muted-foreground">
            Postal Code
          </label>
          <Input
            id="zip_code"
            {...register("zip_code")}
            className="bg-card border-card-foreground/10"
          />
          {errors.zip_code && (
            <p className="text-red-500 text-sm">{errors.zip_code.message}</p>
          )}
        </div>

        <div className="space-y-2">
          <label htmlFor="country" className="text-muted-foreground">
            Country
          </label>
          <Select
            defaultValue={countryValue}
            onValueChange={(value) => setValue("country", value)}
          >
            <SelectTrigger className="w-full bg-card border-card-foreground/10">
              <SelectValue placeholder="Select a country" />
            </SelectTrigger>
            <SelectContent>
              {countries.map((country) => (
                <SelectItem key={country.value} value={country.value}>
                  {country.label}
                </SelectItem>
              ))}
              {!countries.some((c) => c.value === countryValue) && (
                <SelectItem value={countryValue}>{countryValue}</SelectItem>
              )}
            </SelectContent>
          </Select>
          {errors.country && (
            <p className="text-red-500 text-sm">{errors.country.message}</p>
          )}
        </div>

        <div className="space-y-2">
          <label htmlFor="birth_date" className="text-muted-foreground">
            Date of Birth
          </label>
          <Input
            id="birth_date"
            type="date"
            {...register("birth_date")}
            className="bg-card border-card-foreground/10 [color-scheme:dark]"
          />
          {errors.birth_date && (
            <p className="text-red-500 text-sm">{errors.birth_date.message}</p>
          )}
        </div>

        <div className="space-y-2">
          <label htmlFor="email" className="text-muted-foreground">
            Email
          </label>
          <Input
            id="email"
            type="email"
            {...register("email")}
            className="bg-card border-card-foreground/10"
          />
          {errors.email && (
            <p className="text-red-500 text-sm">{errors.email.message}</p>
          )}
        </div>

        <div className="space-y-2">
          <label htmlFor="phone" className="text-muted-foreground">
            Phone
          </label>
          <Input
            id="phone"
            type="tel"
            {...register("phone")}
            className="bg-card border-card-foreground/10"
          />
          {errors.phone && (
            <p className="text-red-500 text-sm">{errors.phone.message}</p>
          )}
        </div>

        <div className="space-y-2">
          <label htmlFor="account_type" className="text-muted-foreground">
            Account Type
          </label>
          <Input
            id="account_type"
            value={user?.account_type?.title || "Basic Plan"}
            disabled
            className="bg-card border-card-foreground/10 opacity-70 cursor-not-allowed"
          />
          <p className="text-xs text-muted-foreground">
            Account type cannot be modified
          </p>
        </div>
      </div>

      <div className="flex gap-4">
        <Button type="submit" disabled={isLoading}>
          {isLoading ? "Submitting..." : "Submit"}
        </Button>
        <Button
          type="button"
          variant="outline"
          onClick={() => {
            // Reset form to default values
            Object.keys(formSchema.shape).forEach((key) => {
              setValue(key as keyof FormValues, "");
            });
            setImage(null);
          }}
        >
          Reset
        </Button>
      </div>

      <div className="mt-4">
        <h2 className="text-l font-bold mb-6">PROFILE PHOTO</h2>

        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
          <Card
            className="flex flex-col items-center justify-center p-6 h-full min-h-[400px] bg-card border-card-foreground/10 cursor-pointer"
            onDragOver={handleDragOver}
            onDragLeave={handleDragLeave}
            onDrop={handleDrop}
            onClick={handleClick}
          >
            <div className="flex flex-col items-center justify-center w-full h-full">
              <div
                className={`relative flex items-center justify-center w-64 h-64 rounded-full mb-6 transition-all ${
                  isDragging ? "scale-110" : ""
                }`}
                style={{
                  background:
                    "radial-gradient(circle, rgba(34,197,94,0.2) 0%, rgba(17,24,39,1) 70%)",
                }}
              >
                {image ? (
                  <img
                    src={image || "/placeholder.svg"}
                    alt="Profile"
                    className="w-56 h-56 rounded-full object-cover"
                  />
                ) : (
                  <User className="w-32 h-32 text-muted-foreground/50" />
                )}
              </div>
              <p className="text-muted-foreground text-center">
                Drop a file on the circle above to upload
              </p>
            </div>
          </Card>
          <PhotoGuidelines />
        </div>
      </div>
    </form>
  );
}
